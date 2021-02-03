<?php

declare(strict_types=1);

namespace brokiem\simplesleep;

use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerBedLeaveEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class SimpleSleep extends PluginBase implements Listener
{
    /** @var string $prefix */
    private $prefix = "§7[§aSimple§2Sleep§7]§r";

    /** @var array */
    private $sleepingPlayer;

    /** @var bool */
    private $isTaskRun = false;

    public function onEnable()
    {
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        switch (strtolower($command->getName())) {
            case "reload":
                $this->reloadConfig();
                $sender->sendMessage($this->prefix . TextFormat::GREEN . " Config reloaded successfully!");
                break;
            case "update":
                $sender->sendMessage($this->prefix . TextFormat::YELLOW . "Checking updates, Please wait...");
                UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
                break;
        }

        return true;
    }

    public function broadcastMessage(string $message)
    {
        if (strtolower($this->getConfig()->get("message-type")) === "message") {
            $this->broadcastMessage($message);
        } elseif (strtolower($this->getConfig()->get("message-type")) === "actionbar") {
            foreach ($this->getServer()->getOnlinePlayers() as $player) {
                $player->sendActionBarMessage($message);
            }
        }
    }

    public function onEnterBed(PlayerBedEnterEvent $event)
    {
        $player = $event->getPlayer();

        if (!$this->getConfig()->get("enable-all-worlds") and
            !in_array($player->getLevel()->getFolderName(), $this->getConfig()->get("enabled-worlds"))
        ) return;

        $this->sleepingPlayer[] = $player->getLowerCaseName();
        $this->broadcastMessage(
            str_replace("{player}",
                $player->getDisplayName(),
                $this->getConfig()->get("on-enter-bed-message", "{player} is sleeping!")
            )
        );

        if (!$this->isTaskRun) {
            if (count($this->sleepingPlayer) >= (int)$this->getConfig()->get("minimal-players", 1)) {
                $this->isTaskRun = true;

                $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick): void {
                    foreach ($this->getServer()->getLevels() as $level) {
                        $level->setTime(0);
                    }

                    $this->isTaskRun = false;
                    $this->sleepingPlayer = [];
                    $this->broadcastMessage($this->getConfig()->get("on-time-change", "It's morning now, wake up!"));
                }), (int)$this->getConfig()->get("sleep-duration", 100));
            }
        }
    }

    public function onLeaveBed(PlayerBedLeaveEvent $event)
    {
        $player = $event->getPlayer();

        if (isset($this->sleepingPlayer[$player->getLowerCaseName()])) {
            unset($this->sleepingPlayer[$player->getLowerCaseName()]);
        }
    }

}