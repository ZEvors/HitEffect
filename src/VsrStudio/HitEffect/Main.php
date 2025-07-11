<?php

declare(strict_types=1);

namespace VsrStudio\HitEffect;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\world\particle\{HeartParticle, InkParticle, FlameParticle, LavaParticle, WaterParticle, SmokeParticle};
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use onebone\coinapi\CoinAPI;
use jojoe77777\FormAPI\SimpleForm;

class Main extends PluginBase implements Listener {

    private DataConnector $database;
    private CoinAPI $coinapi;

    public function onEnable(): void {
        $this->saveResource("config.yml");
        $this->saveResource("mysql.sql");

        $this->database = libasynql::create($this, $this->getConfig()->get("database"), ["mysql" => "mysql.sql"]);
        $this->database->executeGeneric("hit.create_table");

        $plugin = $this->getServer()->getPluginManager()->getPlugin("CoinAPI");
        if (!$plugin instanceof CoinAPI) {
            $this->getLogger()->error("CoinAPI not found.");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        $this->coinapi = $plugin;

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {
        if (!$sender instanceof Player) return true;

        if ($cmd->getName() === "hiteffect") {
            if (isset($args[0]) && strtolower($args[0]) === "owned") {
                $this->openOwnedForm($sender);
            } else {
                $this->openShopForm($sender);
            }
            return true;
        }

        return false;
    }

    public function onDamage(EntityDamageByEntityEvent $event): void {
        if ($event->isCancelled()) return;

        $damager = $event->getDamager();
        $entity = $event->getEntity();
        if (!$damager instanceof Player || !$entity instanceof Player) return;

        $name = strtolower($damager->getName());

        $this->database->executeSelect("hit.load", ["name" => $name], function(array $rows) use ($entity) {
            if (!isset($rows[0])) return;

            $id = $rows[0]["current"] ?? "";
            if ($id === "") return;

            $pos = $entity->getPosition();
            $world = $entity->getWorld();

            switch ($id) {
                case "heart":
                    $world->addParticle($pos, new HeartParticle(1));
                    break;
                case "ink":
                    $world->addParticle($pos, new InkParticle(1));
                    break;
                case "flame":
                    $world->addParticle($pos, new FlameParticle());
                    break;
                case "lava":
                    $world->addParticle($pos, new LavaParticle());
                    break;
                case "water":
                    $world->addParticle($pos, new WaterParticle());
                    break;
                case "smoke":
                    $world->addParticle($pos, new SmokeParticle(1));
                    break;
            }
        });
    }

    public function openShopForm(Player $player): void {
        $name = strtolower($player->getName());

        $this->database->executeSelect("hit.load", ["name" => $name], function(array $rows) use ($player) {
            $owned = isset($rows[0]) ? explode(",", $rows[0]["unlocked"]) : [];

            $form = new SimpleForm(function(Player $player, $data = null) use ($owned) {
                if ($data === null) return;

                $effects = $this->getConfig()->get("effects");
                $info = $effects[$data] ?? null;
                if ($info === null) return;

                $perm = $info["permission"] ?? "";
                $price = $info["price"] ?? 10000;

                if (!empty($perm) && !$player->hasPermission($perm)) {
                    $player->sendMessage("§cKamu tidak memiliki izin.");
                    return;
                }

                if (in_array($data, $owned)) {
                    $this->saveEffect($player, $data, false);
                    $player->sendMessage("§aEfek §b$data §adipilih.");
                    return;
                }

                if ($this->coinapi->myCoin($player) >= $price) {
                    $this->coinapi->reduceCoin($player, $price);
                    $this->saveEffect($player, $data);
                    $player->sendMessage("§aEfek §b$data §aberhasil dibeli dan dipilih.");
                } else {
                    $player->sendMessage("§cKoin tidak cukup.");
                }
            });

            $form->setTitle("§l§bHit Effect Shop");

            foreach ($this->getConfig()->get("effects") as $id => $info) {
                $perm = $info["permission"] ?? "";
                if (!empty($perm) && !$player->hasPermission($perm)) continue;

                $label = $info["button"] ?? ucfirst($id);
                $line2 = in_array($id, $owned) ? "§aOwned" : "§6{$info["price"]} Coins";
                $form->addButton("§f$label\n$line2", -1, "", $id);
            }

            $player->sendForm($form);
        });
    }

    public function openOwnedForm(Player $player): void {
        $name = strtolower($player->getName());

        $this->database->executeSelect("hit.load", ["name" => $name], function(array $rows) use ($player) {
            if (!isset($rows[0]) || empty($rows[0]["unlocked"])) {
                $player->sendMessage("§cKamu belum memiliki efek.");
                return;
            }

            $owned = array_filter(explode(",", $rows[0]["unlocked"]));
            $form = new SimpleForm(function(Player $player, $data = null) use ($owned) {
                if ($data === null) return;

                $this->saveEffect($player, $data, false);
                $player->sendMessage("§aEfek §b$data §adipilih.");
            });

            $form->setTitle("§l§aOwned Hit Effects");
            $form->setContent("§7Pilih efek yang ingin digunakan:");

            foreach ($owned as $id) {
                $info = $this->getConfig()->get("effects")[$id] ?? null;
                if ($info === null) continue;

                $perm = $info["permission"] ?? "";
                if (!empty($perm) && !$player->hasPermission($perm)) continue;

                $label = $info["button"] ?? ucfirst($id);
                $form->addButton("§f$label", -1, "", $id);
            }

            $player->sendForm($form);
        });
    }

    public function saveEffect(Player $player, string $id, bool $add = true): void {
        $name = strtolower($player->getName());

        $this->database->executeSelect("hit.load", ["name" => $name], function(array $rows) use ($name, $id, $add) {
            $owned = isset($rows[0]) ? explode(",", $rows[0]["unlocked"]) : [];
            if ($add && !in_array($id, $owned)) $owned[] = $id;

            $this->database->executeChange("hit.save", [
                "name" => $name,
                "current" => $id,
                "unlocked" => implode(",", $owned)
            ]);
        });
    }
}
