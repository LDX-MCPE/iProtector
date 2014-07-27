<?php
namespace LDX\iProtector;
use LDX\iProtector\Area;
use pocketmine\math\Vector3;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\Server;
use pocketmine\utils\TextFormat as Color;
use pocketmine\event\entity\EntityDamageEvent;
class Main extends PluginBase implements Listener {
  public function onEnable() {
    $this->getServer()->getPluginManager()->registerEvents($this,$this);
    
  }
  public function onCommand(CommandSender $p,Command $cmd,$label,array $args) {
    if(!($p instanceof Player)) {
      $p->sendMessage(Color::RED . "Command must be used in-game.");
      return true;
    }
    if(!isset($args[0])) {
      return false;
    }
    $n = strtolower($p->getname());
    $action = strtolower($args[0]);
    switch($action) {
      case "pos1":
        $this->pos1[$n] = new Vector3(round($p->getX()),round($p->getY()),round($p->getZ()));
        $o = "Position 1 set to: (" . $this->pos1[$n]->getX() . "," . $this->pos1[$n]->getY() . "," . $this->pos1[$n]->getZ() . ")";
      break;
      case "pos2":
        $this->pos2[$n] = new Vector3($p->getX(),$p->getY(),$p->getZ());
        $o = "Position 2 set to: (" . $this->pos2[$n]->getX() . "," . $this->pos2[$n]->getY() . "," . $this->pos2[$n]->getZ() . ")";
      break;
      case "create":
        if(isset($args[1])) {
          if(isset($this->pos1[$n]) && isset($this->pos2[$n])) {
            if(!isset($this->areas[strtolower($args[1])])) {
              $area = new Area(array("name" => strtolower($args[1]),"flags" => array("edit" => true,"god" => false,"chest" => false),"pos" => array($this->pos1[$n],$this->pos2[$n]),$this));
              $area->save();
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
      break;
      case "flag":
        
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
    
  }
    /**
  * @param EntityExplodeEvent $event
  *
  * @priority HIGHEST
  * @ignoreCancelled false
  */
    public function onExplode(EntityExplodeEvent $event) {
    
  }
  public function onDisable() {
    foreach($this->areas as $area) {
      $area->save();
    }
  }
}
?>
