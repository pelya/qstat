/*
 * qstat
 *
 * OpenLieroX protocol
 * Copyright 2017 Sergii Pylypenko
 *
 * Licensed under the Artistic License, see LICENSE.txt for license terms
 */
#ifndef QSTAT_OPENLIEROX_H
#define QSTAT_OPENLIEROX_H

#include "qstat.h"

query_status_t send_openlieroxmaster_request_packet(struct qserver *server);
query_status_t deal_with_openlieroxmaster_packet(struct qserver *server, char *rawpkt, int pktlen);

query_status_t send_openlierox_request_packet(struct qserver *server);
query_status_t deal_with_openlierox_packet(struct qserver *server, char *rawpkt, int pktlen);

#endif
