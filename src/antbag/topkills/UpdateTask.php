<?php

namespace antbag\topkills;

use pocketmine\scheduler\Task;

class UpdateTask extends Task{

    public function __construct(Main $plugin){
     $this->plugin = $plugin;
    }

    public function onRun(): void{
     $lb = $this->plugin->getLeaderBoard();
     $list = $this->plugin->getParticles();
     foreach($list as $particle){
      $particle->setText($lb);
     }
    }

}
