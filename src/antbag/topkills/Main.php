<?php

namespace antbag\topkills;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerDeathEvent;

class Main extends PluginBase implements Listener {

    private $particle = [];
    private $data;

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder() . "data");
        $this->saveResource("setting.yml");
        $this->config = (new Config($this->getDataFolder() . "config.yml", Config::YAML))->getAll();
        $this->data = new Config($this->getDataFolder() . "data/kills.yml", Config::YAML);
        if (empty($this->config["positions"])) {
            $this->getServer()->getLogger()->Info("Please Set Location");
            return;
        }

        $pos = $this->config["positions"];
        $this->particle[] = new FloatingText($this, new Vector3($pos[0], $pos[1], $pos[2]));
        $this->getScheduler()->scheduleRepeatingTask(new UpdateTask($this), 40);
        $this->getServer()->getLogger()->Info("Location Have Been Load");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "topkill") {
            if (!$sender instanceof Player) return false;
            $config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
            $config->set("positions", [round($sender->getPosition()->getX()), round($sender->getPosition()->getY()), round($sender->getPosition()->getZ())]);
            $config->save();
        }
        return true;
    }

    public function Attack(PlayerDeathEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        $damageCause = $event->getEntity()->getLastDamageCause();
        if (!$damageCause instanceof EntityDamageByEntityEvent) return;
        
        /** @var EntityDamageByEntityEvent $damageCause */
        if (!$damageCause->getDamager() instanceof Player) return;
        $data = $this->data;
        $up = $data->get($name);
        $data->set($name, $up + 1);
        $data->save();
    }

    public function createtopten(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $w = $this->getConfig()->get("world");
        $world = $player->getWorld()->getDisplayName() === "$w";
        $top = $this->getConfig()->get("enable");

        if ($world) {
            if ($top == "true") {
                $this->getLeaderBoard();
            }
        }
    }

    public function settopdata(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();

        $farm = $this->data;
        if (!$farm->exists($name)) {
            $farm->set($name, 0);
            $farm->save();
        }
    }

    public function getLeaderBoard(): string {
        $data = $this->data;
        $setting = new Config($this->getDataFolder() . "setting.yml", Config::YAML);
        $swallet = $data->getAll();
        $message = "";
        $top = $setting->get("title-lb");

        if (count($swallet) > 0) {
            arsort($swallet);
            $i = 1;
            foreach ($swallet as $name => $amount) {
                $tags = str_replace(["{num}", "{player}", "{amount}"], [$i, $name, $amount], $setting->get("text-lb")) . "\n";
                $message .= "\n " . $tags;

                if ($i >= 10) {
                    break;
                }
                ++$i;
            }
        }
        $return = (string) $top . $message;
        return $return;
    }

    public function getParticles(): array {
        return $this->particle;
    }
}
