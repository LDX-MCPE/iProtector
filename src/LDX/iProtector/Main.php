<?php
namespace LDX\iProtector;

use pocketmine\math\Vector3;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as Color;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;

class Main extends PluginBase implements CommandExecutor, Listener {
  public function onEnable() {
    $this->getServer()->getPluginManager()->registerEvents($this,$this);
    @mkdir($this->getDataFolder());
    if(!file_exists($this->getDataFolder() . "areas.dat")) {
      file_put_contents($this->getDataFolder() . "areas.dat",yaml_emit(array()));
    }
    $this->areas = array();
    $this->areadata = yaml_parse(file_get_contents($this->getDataFolder() . "areas.dat"));
    foreach($this->areadata as $data) {
      $area = new Area($data,$this);
    }
  }
  public function onCommand(CommandSender $p,Command $cmd,$label,array $args) {
    if(!($p instanceof Player)) {
      $p->sendMessage(Color::RED . "Command must be used in-game.");
      return true;
    }
    if(!isset($args[0])) {
      return false;
    }
    $n = strtolower($p->getName());
    $action = strtolower($args[0]);
    switch($action) {
      case "pos1":
        if($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.pos1")) {
          $this->pos1[$n] = new Vector3(round($p->getX()),round($p->getY()),round($p->getZ()));
          $o = "Position 1 set to: (" . $this->pos1[$n]->getX() . "," . $this->pos1[$n]->getY() . "," . $this->pos1[$n]->getZ() . ")";
        } else {
          $o = "You do not have permission to use this subcommand.";
        }
      break;
      case "pos2":
        if($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.pos2")) {
          $this->pos2[$n] = new Vector3(round($p->getX()),round($p->getY()),round($p->getZ()));
          $o = "Position 2 set to: (" . $this->pos2[$n]->getX() . "," . $this->pos2[$n]->getY() . "," . $this->pos2[$n]->getZ() . ")";
        } else {
          $o = "You do not have permission to use this subcommand.";
        }
      break;
      case "create":
        if($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.create")) {
          if(isset($args[1])) {
            if(isset($this->pos1[$n]) && isset($this->pos2[$n])) {
              if(!isset($this->areas[strtolower($args[1])])) {
                $area = new Area(array("name" => strtolower($args[1]),"flags" => array("edit" => true,"god" => false,"chest" => false),"pos1" => array($this->pos1[$n]->getX(),$this->pos1[$n]->getY(),$this->pos1[$n]->getZ()),"pos2" => array($this->pos2[$n]->getX(),$this->pos2[$n]->getY(),$this->pos2[$n]->getZ())),$this);
                unset($this->pos1[$n]);
                unset($this->pos2[$n]);
                $o = "Area created!";
              } else {
                $o = "An area with that name already exists.";
              }
            } else {
              $o = "Please select both positions first.";
            }
          } else {
            $o = "Please specify a name for this area.";
          }
        } else {
          $o = "You do not have permission to use this subcommand.";
        }
      break;
      case "list":
        if($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.list")) {
          $o = "Areas:";
          foreach($this->areas as $area) {
            $o = $o . " " . $area->getName() . ";";
          }
        }
      break;
      case "flag":
        if($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.flag")) {
          if(isset($args[1])) {
            if(isset($this->areas[strtolower($args[1])])) {
              $area = $this->areas[strtolower($args[1])];
              if(isset($args[2])) {
                if(isset($area->flags[strtolower($args[2])])) {
                  $flag = strtolower($args[2]);
                  if(isset($args[3]) && strtolower($args[3]) == ("on" || "off" || "true" || "false")) {
                    $mode = strtolower($args[3]);
                    if($mode == ("true" || "on")) {
                      $mode = true;
                    } else {
                      $mode = false;
                    }
                    $area->setFlag($flag,$mode);
                  } else {
                    $area->toggleFlag($flag);
                  }
                  if($area->getFlag($flag)) {
                    $status = "on";
                  } else {
                    $status = "off";
                  }
                  $o = "Flag " . $flag . " set to " . $status . " for area " . $area->getName() . "!";
                } else {
                  $o = "Flag not found. (Flags: edit, god)";
                }
              } else {
                $o = "Please specify a flag. (Flags: edit, god)";
              }
            } else {
              $o = "Area doesn't exist.";
            }
          } else {
            $o = "Please specify the area you would like to flag.";
          }
        } else {
          $o = "You do not have permission to use this subcommand.";
        }
      break;
      case "delete":
        if($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.delete")) {
          if(isset($args[1])) {
            if(isset($this->areas[strtolower($args[1])])) {
              $area = $this->areas[strtolower($args[1])];
              $area->delete();
              $o = "Area deleted!";
            } else {
              $o = "Area does not exist.";
            }
          } else {
            $o = "Please specify an area to delete.";
          }
        } else {
          $o = "You do not have permission to use this subcommand.";
        }
      break;
      default:
        return false;
      break;
    }
    $p->sendMessage($o);
    return true;
  }
  /**
  * @param EntityDamageEvent $event
  *
  * @priority HIGHEST
  * @ignoreCancelled true
  */
  public function onHurt(EntityDamageEvent $event) {
    if($event->getEntity() instanceof Player) {
      $p = $event->getEntity();
      $cancel = false;
      $pos = new Vector3($p->getX(),$p->getY(),$p->getZ());
      foreach($this->areas as $area) {
        if($area->contains($pos) && $area->getFlag("god")) {
          $cancel = true;
        }
      }
      if($cancel) {
        $event->setCancelled();
      }
    }
  }
  /**
  * @param BlockBreakEvent $event
  *
  * @priority HIGHEST
  * @ignoreCancelled true
  */
  public function onBlockBreak(BlockBreakEvent $event) {
    $b = $event->getBlock();
    $p = $event->getPlayer();
    $cancel = false;
    $pos = new Vector3($b->x,$b->y,$b->z);
    foreach($this->areas as $area) {
      if($area->contains($pos) && $area->getFlag("edit") && !($p->hasPermission("iprotector") || $p->hasPermission("iprotector.access"))) {
        $cancel = true;
      }
    }
    if($cancel) {
      $event->setCancelled();
    }
  }
  /**
  * @param BlockPlaceEvent $event
  *
  * @priority HIGHEST
  * @ignoreCancelled true
  */
  public function onBlockPlace(BlockPlaceEvent $event) {
    $b = $event->getBlock();
    $p = $event->getPlayer();
    $cancel = false;
    $pos = new Vector3($b->x,$b->y,$b->z);
    foreach($this->areas as $area) {
      if($area->contains($pos) && $area->getFlag("edit") && !($p->hasPermission("iprotector") || $p->hasPermission("iprotector.access"))) {
        $cancel = true;
      }
    }
    if($cancel) {
      $event->setCancelled();
    }
  }
  public function saveAreas() {
    file_put_contents($this->getDataFolder() . "areas.dat",yaml_emit($this->areadata));
  }
  public function onDisable() {
    $this->saveAreas();
  }
}
?>
