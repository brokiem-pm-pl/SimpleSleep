<?php

declare(strict_types=1);

/*
 *  _____ _                 _      _____ _
 * /  ___(_)               | |    /  ___| |
 * \ `--. _ _ __ ___  _ __ | | ___\ `--.| | ___  ___ _ __
 *  `--. \ | '_ ` _ \| '_ \| |/ _ \`--. \ |/ _ \/ _ \ '_ \
 * /\__/ / | | | | | | |_) | |  __/\__/ / |  __/  __/ |_) |
 * \____/|_|_| |_| |_| .__/|_|\___\____/|_|\___|\___| .__/
 *                 | |                            | |
 *                 |_|                            |_|
 *
 * Copyright (C) 2021 brokiem
 *
 * This software is distributed under "GNU General Public License v3.0".
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License v3.0
 * along with this program. If not, see
 * <https://opensource.org/licenses/GPL-3.0>.
 */

namespace brokiem\simplesleep;

use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerBedLeaveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class SimpleSleep extends PluginBase implements Listener
{
    /** @var string $prefix */
    private $prefix = "§7[§aSimple§2Sleep§7]§r";

    /** @var array $sleepingPlayer */
    private $sleepingPlayer = [];

    /** @var bool $isTaskRun */
    private $isTaskRun = false;

    /** @var array $delayedPlayer */
    private $delayedPlayer = [];

    public function onEnable()
    {
        $this->saveDefaultConfig();
        $this->checkConfig();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
    }

    private function checkConfig()
    {
        if ($this->getConfig()->get("config-version") !== 1.0) {
            $this->getLogger()->notice("Your configuration file is outdated, updating the config.yml...");
            $this->getLogger()->notice("The old configuration file can be found at config.old.yml");

            rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config.old.yml");

            $this->saveDefaultConfig();
            $this->reloadConfig();
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if (!isset($args[0])) return false;

        switch (strtolower($args[0])) {
            case "reload":
                $this->reloadConfig();
                $sender->sendMessage($this->prefix . TextFormat::GREEN . " Config reloaded successfully!");
                break;
        }

        return true;
    }

    /**
     * @param string $message
     */
    public function broadcastMessage(string $message)
    {
        if (strtolower($this->getConfig()->get("message-type", "actionbar")) === "message") {
            $this->getServer()->broadcastMessage($message);
        } elseif (strtolower($this->getConfig()->get("message-type", "actionbar")) === "actionbar") {
            foreach ($this->getServer()->getOnlinePlayers() as $player) {
                $player->sendActionBarMessage($message);
            }
        }
    }

    public function onEnterBed(PlayerBedEnterEvent $event)
    {
        $player = $event->getPlayer();

        if (!$this->getConfig()->get("enable-all-worlds") and !in_array($player->getLevel()->getFolderName(), $this->getConfig()->get("enabled-worlds"))) return;

        $this->sleepingPlayer[] = $player->getLowerCaseName();

        if (!isset($this->delayedPlayer[$player->getLowerCaseName()])) { // to prevent spamming
            $this->broadcastMessage(
                str_replace("{player}",
                    $player->getDisplayName(),
                    $this->getConfig()->get("on-enter-bed-message", "{player} is sleeping now!")
                )
            );

            $this->delayedPlayer[] = $player->getLowerCaseName();

            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($player):void {
                if (isset($this->delayedPlayer[$player->getLowerCaseName()])) {
                    unset($this->delayedPlayer[$player->getLowerCaseName()]);
                }
            }), 600);
        }

        if (!$this->isTaskRun) {
            if (count($this->sleepingPlayer) >= (int)$this->getConfig()->get("minimal-players", 1)) {
                $this->isTaskRun = true;

                $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick): void {
                    /** @var string $enabledWorlds */
                    foreach ((array)$this->getConfig()->get("enabled-worlds") as $enabledWorlds) {
                        $world = $this->getServer()->getLevelByName($enabledWorlds);

                        if ($world instanceof Level) {
                            if ($this->getServer()->isLevelLoaded($enabledWorlds)) {
                                $world->setTime(Level::TIME_DAY);
                            }
                        }
                    }

                    foreach ($this->sleepingPlayer as $name) {
                        $sleepingPlayer = $this->getServer()->getPlayerExact($name);

                        if ($sleepingPlayer !== null) {
                            $sleepingPlayer->stopSleep(); // otherwise, the player wouldn't wake up in the morning
                        }
                    }

                    $this->isTaskRun = false;
                    $this->sleepingPlayer = [];
                }), (int)$this->getConfig()->get("sleep-duration", 120));
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

    public function onQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();

        if (isset($this->sleepingPlayer[$player->getLowerCaseName()])) {
            unset($this->sleepingPlayer[$player->getLowerCaseName()]);
        }

        if (isset($this->delayedPlayer[$player->getLowerCaseName()])) {
            unset($this->delayedPlayer[$player->getLowerCaseName()]);
        }
    }
}