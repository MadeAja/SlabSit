## SlabSit
Sit on the stair block
<img src="https://github.com/brokiem/SlabSit/blob/master/assets/slabsit.PNG">

# Feature
You can sit on the slab block

# How to use
Install this plugin and run your server. place stair block and tap (or click) it. To cancel, jump or sneak.

# Command 
```/sit``` - Toggle to sit on the stair block. To use this command, you must set ```register-sit-command``` in the config.yml to ```true``` (Permission:``` slabsit.toggle```)

# Settings
You can customize some settings.
To edit, open config.yml in plugin folder.

### Developer Documentation
 * If you check whether player is sitting on the stair, please call method isSitting from StairSeat class
```php
/** @var \pocketmine\Player $player */
isSitting(Player $player) : bool
```
