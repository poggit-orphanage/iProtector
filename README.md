# iProtector fork
PocketMine plugin originaly made by [LDX-MCPE](https://github.com/LDX-MCPE/iProtector), currently maintained by [poggit-orphanage](https://github.com/poggit-orphanage/iProtector) 

Default protection settings in onfig.yml file and straight forward commands to create area's and set flags for specific area’s. 

Command examples:
- /area <pos1/pos2/create/flag/list/delete>
- /area pos1
- /area pos2
- /area create <areaname>
- /area flag <areaname> <permission>

usage ie.
- /area flag Myarea edit false
- /area flag Myarea touch false

In progress 

- /area text <areaname> <textfield:enter/info/url..> <string>

- [x] Enter and leave area detect
- [x] send notification to player on area enter or leave
- [x] add command to add area custom messages 
- [x] add config vars to set default messages for all area's 
- [x] add config var to turn all messages on/off
- [ ] add config var to turn all commands on/off
- [ ] add methode to run commands on player/area
- [x] add area custom commands
- [x] much more ideas to work on (events, level/points, artifacts etc.)
