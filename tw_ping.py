#!/usr/bin/python

# coding: utf-8
from socket import *
import struct
import sys
import threading
import time
import datetime



NUM_MASTERSERVERS = 1
MASTERSERVER_PORT = 8300

TIMEOUT = 2

SERVERTYPE_NORMAL = 0
SERVERTYPE_LEGACY = 1

PACKET_GETLIST = "\x20\x00\x00\x00\x00\x00\xff\xff\xff\xffreqt"
PACKET_GETLIST2 = "\x20\x00\x00\x00\x00\x00\xff\xff\xff\xffreq2"
PACKET_GETINFO = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xffgief"
PACKET_GETINFO2 = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xffgie2" + "\x00"
PACKET_GETINFO3 = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xffgie3" + "\x00"



class Server_Info(threading.Thread):

	def __init__(self, address, type):
		self.address = address
		self.type = type
		self.finished = False
		threading.Thread.__init__(self, target = self.run)

	def run(self):
		self.info = None
		if self.type == SERVERTYPE_NORMAL:
			self.info = get_server_info3(self.address)
		elif self.type == SERVERTYPE_LEGACY:
			self.info = get_server_info(self.address)
			if self.info:
				self.info = get_server_info2(self.address)
		self.finished = True


def get_server_info(address):
	try:
		sock = socket(AF_INET, SOCK_DGRAM)
		sock.settimeout(TIMEOUT);
		sock.sendto(PACKET_GETINFO, address)
		data, addr = sock.recvfrom(1024)
		sock.close()

		data = data[14:] # skip header
		slots = data.split("\x00")

		server_info = {}
		server_info["version"] = slots[0]
		server_info["name"] = slots[1]
		server_info["map"] = slots[2]
		server_info["gametype"] = slots[3]
		server_info["flags"] = int(slots[4])
		server_info["progression"] = int(slots[5])
		server_info["num_players"] = int(slots[6])
		server_info["max_players"] = int(slots[7])
		server_info["players"] = []

		for i in xrange(0, server_info["num_players"]):
			player = {}
			player["name"] = slots[8+i*2]
			player["score"] = int(slots[8+i*2+1])
			server_info["players"].append(player)

		return server_info

	except:
		sock.close()
		return None


def get_server_info2(address):
	try:
		sock = socket(AF_INET, SOCK_DGRAM)
		sock.settimeout(TIMEOUT);
		sock.sendto(PACKET_GETINFO2, address)
		data, addr = sock.recvfrom(1024)
		sock.close()

		data = data[14:] # skip header
		slots = data.split("\x00")

		server_info = {}
		server_info["token"] = slots[0]
		server_info["version"] = slots[1]
		server_info["name"] = slots[2]
		server_info["map"] = slots[3]
		server_info["gametype"] = slots[4]
		server_info["flags"] = int(slots[5])
		server_info["progression"] = int(slots[6])
		server_info["num_players"] = int(slots[7])
		server_info["max_players"] = int(slots[8])
		server_info["players"] = []

		for i in xrange(0, server_info["num_players"]):
			player = {}
			player["name"] = slots[9+i*2]
			player["score"] = int(slots[9+i*2+1])
			server_info["players"].append(player)

		return server_info

	except:
		sock.close()
		return None


def get_server_info3(address):
	try:
		sock = socket(AF_INET, SOCK_DGRAM)
		sock.settimeout(TIMEOUT);
		sendtime = datetime.datetime.now()
		sock.sendto(PACKET_GETINFO3, address)
		data, addr = sock.recvfrom(1400)
		recvtime = datetime.datetime.now()
		sock.close()

		data = data[14:] # skip header
		slots = data.split("\x00")

		server_info = {}
		server_info["token"] = slots[0]
		server_info["version"] = slots[1]
		server_info["name"] = slots[2]
		server_info["map"] = slots[3]
		server_info["gametype"] = slots[4]
		server_info["flags"] = int(slots[5])
		server_info["num_players"] = int(slots[6])
		server_info["max_players"] = int(slots[7])
		server_info["num_clients"] = int(slots[8])
		server_info["max_clients"] = int(slots[9])
		server_info["players"] = []
		server_info["ping"] = (recvtime.second * 1000 + recvtime.microsecond / 1000) - (sendtime.second * 1000 + sendtime.microsecond / 1000)

		for i in xrange(0, server_info["num_clients"]):
			player = {}
			player["name"] = slots[10+i*5]
			player["clan"] = slots[10+i*5+1]
			player["country"] = int(slots[10+i*5+2])
			player["score"] = int(slots[10+i*5+3])
			if int(slots[10+i*5+4]):
				player["player"] = True
			else:
				player["player"] = False
			server_info["players"].append(player)

		return server_info

	except:
		sock.close()
		return None



class Master_Server_Info(threading.Thread):

	def __init__(self, address):
		self.address = address
		self.finished = False
		threading.Thread.__init__(self, target = self.run)

	def run(self):
		self.servers = get_list(self.address) + get_list2(self.address)
		self.finished = True


def get_list(address):
	servers = []

	try:
		sock = socket(AF_INET, SOCK_DGRAM)
		sock.settimeout(TIMEOUT)
		sock.sendto(PACKET_GETLIST, address)

		while 1:
			data, addr = sock.recvfrom(1024)

			data = data[14:]
			num_servers = len(data) / 6

			for n in range(0, num_servers):
				ip = ".".join(map(str, map(ord, data[n*6:n*6+4])))
				port = ord(data[n*6+5]) * 256 + ord(data[n*6+4])
				servers += [[(ip, port), SERVERTYPE_LEGACY]]

	except:
		sock.close()

	return servers


def get_list2(address):
	servers = []

	try:
		sock = socket(AF_INET, SOCK_DGRAM)
		sock.settimeout(TIMEOUT)
		sock.sendto(PACKET_GETLIST2, address)

		while 1:
			data, addr = sock.recvfrom(1400)
			
			data = data[14:]
			num_servers = len(data) / 18

			for n in range(0, num_servers): 
				if data[n*18:n*18+12] == "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff":
					ip = ".".join(map(str, map(ord, data[n*18+12:n*18+16])))
				else:
					ip = ":".join(map(str, map(ord, data[n*18:n*18+16])))
				port = (ord(data[n*18+16])<<8) + ord(data[n*18+17])
				servers += [[(ip, port), SERVERTYPE_NORMAL]]

	except:
		sock.close()

	return servers


servers_info = []
address="127.0.0.1:8303"
if len(sys.argv) > 1:
	address=sys.argv[1]

s = Server_Info(address, SERVERTYPE_NORMAL)
servers_info.append(s)
s.start()
time.sleep(0.01) # avoid issues

num_players = 0
num_clients = 0

while len(servers_info) != 0:
	if servers_info[0].finished == True:

		if servers_info[0].info:
			num_players += servers_info[0].info["num_players"]
			if servers_info[0].type == SERVERTYPE_NORMAL:
				num_clients += servers_info[0].info["num_clients"]
			else:
				num_clients += servers_info[0].info["num_players"]
			print ("Server " + str(servers_info[0].address) + " " + servers_info[0].info["name"] +
					" ping " + str(servers_info[0].info["ping"]) +
					" players " + str(servers_info[0].info["num_players"]) + "/" + str(servers_info[0].info["max_players"]))
		else:
			print ("No response from server " + str(servers_info[0].address))
			sys.exit(1)

		del servers_info[0]

	time.sleep(0.1) # be nice

sys.exit(0)
