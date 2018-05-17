# Munin server usage statistics

## Requirements
- Apache installed
- Munin installed

## Installation

Copy or symlink relevant munin scripts to /usr/share/munin/plugins

- openlierox_stats_ will monitor OpenLieroX servers
- oa_stats_ will monitor OpenArena servers
- ninslash_stats_ will monitor Ninslash servers

Run:

    sudo munin-node-configure --shell | grep -E 'openlierox_stats_' | sudo sh
    sudo munin-node-configure --shell | grep -E 'oa_stats_' | sudo sh
    sudo munin-node-configure --shell | grep -E 'ninslash_stats_' | sudo sh
    sudo chown root:munin /etc/munin/plugins
    sudo chmod 775 /etc/munin/plugins

# Edit file /etc/cron.d/munin to increase data collection rate for munin-cron from 5 minutes to 1 minute:

    * * * * *     munin if [ -x /usr/bin/munin-cron ]; then /usr/bin/munin-cron; fi

# Enable `graph_strategy cgi` in Munin, as described here: https://wiki.debian.org/Munin/ApacheConfiguration

If you wish to activate Web PUSH notifications for OpenLieroX servers,
replace the line `NOTIFY_SUBSCRIBERS_SCRIPT` at the top of `/usr/share/munin/plugins/openlierox_stats_`
to the path to your qstat repository.

This plugin uses environment variable `QSTAT_PATH`, which is set to `/home/oa/qstat` by default.
