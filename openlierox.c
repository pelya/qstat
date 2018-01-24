/*
 * qstat
 *
 * OpenLieroX protocol
 * Copyright 2017 Sergii Pylypenko
 *
 * Licensed under the Artistic License, see LICENSE.txt for license terms
 */

#include <sys/types.h>
#ifndef _WIN32
 #include <sys/socket.h>
#endif
#include <stdio.h>
#include <stdlib.h>

#include "qstat.h"
#include "qserver.h"
#include "debug.h"

static char master_reply[] = "\xff\xff\xff\xfflx::serverlist2";
static char server_reply[] = "\xff\xff\xff\xfflx::queryreturn";

query_status_t
deal_with_openlieroxmaster_packet(struct qserver *server, char *rawpkt, int pktlen)
{
	unsigned num_servers, i;
	int previous_len;
	char *addresses;

	server->ping_total += time_delta(&packet_recv_time, &server->packet_time1);
	server->server_name = MASTER;

	if (pktlen <= strlen(master_reply) + 2 || memcmp(rawpkt, master_reply, strlen(master_reply) + 1) != 0) {
		malformed_packet(server, "invalid packet header");
		return (PKT_ERROR);
	}
	rawpkt += strlen(master_reply) + 1;
	pktlen -= strlen(master_reply) + 1;

	num_servers = (unsigned char)rawpkt[0];
	rawpkt += 1;
	pktlen -= 1;
	if (num_servers <= 0) {
		return (DONE_AUTO);
	}

	previous_len = server->master_pkt_len;
	server->n_servers += num_servers;
	server->master_pkt_len += num_servers * 6;
	server->master_pkt = (char *)realloc(server->master_pkt, server->master_pkt_len);
	addresses = server->master_pkt + previous_len;

	for (i = 0; i < num_servers; i ++) {
		unsigned pos;

		char addr[64] = "";
		char name[64] = "";
		unsigned numplayers;
		unsigned maxplayers;
		unsigned state;
		char version[64] = "";
		unsigned joinDuringMatch;
		unsigned a0, a1, a2, a3, port;

		for (pos = 0; pktlen > 0 && rawpkt[pos] != 0 && pos < sizeof(addr) - 1; pos++) {
			addr[pos] = rawpkt[pos];
		}
		addr[pos] = 0;
		rawpkt += pos + 1;
		pktlen -= pos + 1;

		for (pos = 0; pktlen > 0 && rawpkt[pos] != 0 && pos < sizeof(addr) - 1; pos++) {
			name[pos] = rawpkt[pos];
		}
		name[pos] = 0;
		rawpkt += pos + 1;
		pktlen -= pos + 1;

		if (pktlen < 3) {
			malformed_packet(server, "invalid packet");
			return (PKT_ERROR);
		}
		numplayers = (unsigned char)rawpkt[0];
		maxplayers = (unsigned char)rawpkt[1];
		state = (unsigned char)rawpkt[2];
		rawpkt += 3;
		pktlen -= 3;

		for (pos = 0; pktlen > 0 && rawpkt[pos] != 0 && pos < sizeof(addr) - 1; pos++) {
			version[pos] = rawpkt[pos];
		}
		version[pos] = 0;
		rawpkt += pos + 1;
		pktlen -= pos + 1;

		if (pktlen < 1) {
			malformed_packet(server, "invalid packet");
			return (PKT_ERROR);
		}
		joinDuringMatch = (unsigned char)rawpkt[0];
		rawpkt += 1;
		pktlen -= 1;

		if (sscanf(addr, "%u.%u.%u.%u:%u", &a0, &a1, &a2, &a3, &port) != 5) {
			malformed_packet(server, "invalid packet");
			return (PKT_ERROR);
		}
		addresses[i * 6 + 0] = a0;
		addresses[i * 6 + 1] = a1;
		addresses[i * 6 + 2] = a2;
		addresses[i * 6 + 3] = a3;
		addresses[i * 6 + 4] = port / 0x100;
		addresses[i * 6 + 5] = port % 0x100;
	}

	return (DONE_AUTO);
}


query_status_t
deal_with_openlierox_packet(struct qserver *server, char *rawpkt, int pktlen)
{
	unsigned pos;

	char addr[64] = "";
	char name[64] = "";
	unsigned numplayers;
	unsigned maxplayers;
	unsigned state;
	char version[64] = "";
	unsigned joinDuringMatch;
	const char * gametype = "";

	server->n_servers++;
	server->n_requests = 1;
	server->ping_total += time_delta(&packet_recv_time, &server->packet_time1);

	if (pktlen <= strlen(server_reply) + 2 || memcmp(rawpkt, server_reply, strlen(server_reply) + 1) != 0) {
		malformed_packet(server, "invalid packet header");
		return (PKT_ERROR);
	}
	rawpkt += strlen(server_reply) + 1;
	pktlen -= strlen(server_reply) + 1;

	for (pos = 0; pktlen > 0 && rawpkt[pos] != 0 && pos < sizeof(addr) - 1; pos++) {
		name[pos] = rawpkt[pos];
	}
	name[pos] = 0;
	rawpkt += pos + 1;
	pktlen -= pos + 1;

	printf("\nserver %s ping_total %d\n", name, server->ping_total);

	if (pktlen < 3) {
		malformed_packet(server, "invalid packet");
		return (PKT_ERROR);
	}
	numplayers = (unsigned char)rawpkt[0];
	maxplayers = (unsigned char)rawpkt[1];
	state = (unsigned char)rawpkt[2];
	rawpkt += 4;
	pktlen -= 4;

	for (pos = 0; pktlen > 0 && rawpkt[pos] != 0 && pos < sizeof(addr) - 1; pos++) {
		version[pos] = rawpkt[pos];
	}
	version[pos] = 0;

	server->server_name = strdup(name);
	server->num_players = numplayers;
	server->max_players = maxplayers;
	switch (state) {
		case 0: gametype = "Open"; break;
		case 1: gametype = "Loading"; break;
		case 2: gametype = "Playing"; break;
		case 3: gametype = "Open/Loading"; break;
		case 4: gametype = "Open/Playing"; break;
		default: gametype = "Open"; break;
	}
	add_rule(server, server->type->game_rule, gametype, NO_FLAGS);
	add_rule(server, "version", version, NO_FLAGS);
	server->map_name = strdup("");
	server->protocol_version = 0;

	return (DONE_FORCE);
}


query_status_t
send_openlieroxmaster_request_packet(struct qserver *server)
{
	return (qserver_send_initial(server, server->type->master_packet, server->type->master_len));
}


query_status_t
send_openlierox_request_packet(struct qserver *server)
{
	return qserver_send_initial(server, server->type->status_packet, server->type->status_len);
	/*
	if (get_server_rules || get_player_info) {
		server->next_rule = "";
	}

	return (INPROGRESS);
	*/
}
