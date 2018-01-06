# iProtector fork
PocketMine plugin made by [LDX-MCPE](https://github.com/LDX-MCPE/iProtector), maintained by [poggit-orphanage](https://github.com/poggit-orphanage/iProtector) 

Default protection settings in config.yml file and straight forward commands to create area's and set flags for specific area’s. 

Command examples:
- /area <pos1/pos2/create/flag/list/delete>
- /area pos1
- /area pos2
- /area create <areaname>
- /area flag <areaname> <permission>

usage:
- /area flag Myarea edit false
- /area flag Myarea touch false

## Area Text
Display messages on area enter and leave.

Textmsg true/false and Leave/Enter prefix text (followed by areaname), default text variables in config.yml 

OR 

set area textfields: 
- enter(replacing the enter-area default text) 
- info (extra description textline)
- url (extra textline not used yet)
- text (extra textline not used yet)

usage:

/area text <areaname> <textfield:enter/info/url/text> <string>

Hide specific area text
if textfield:info is "off" the area text is turned off