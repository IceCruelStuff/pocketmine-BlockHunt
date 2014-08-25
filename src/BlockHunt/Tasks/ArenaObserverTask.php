<?php
namespace BlockHunt\Tasks;

use pocketmine\Player;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\item\Item;
use pocketmine\item\Block;
use BlockHunt\Handlers\ScoreboardHandler;
use BlockHunt\Handlers\SolidBlockHandler;
use BlockHunt\Handlers\ArenaHandler;
use BlockHunt\Handlers\SignsHandler;
use BlockHunt\Entities\ArenaState;
use BlockHunt\Entities\Arena;
use BlockHunt\DisguiseAPI;
use BlockHunt\BlockHunt;
use BlockHunt\ConfigC;

class ArenaObserverTask extends PluginTask{

	private $blockhunt;
	private $configs = array();
	
	public function __construct(BlockHunt $blockhunt){
		parent::__construct($blockhunt);
		$this->blockhunt = $blockhunt;
	}
	
	public function onRun($currentTick){
         foreach($this->blockhunt->storage->arenaList as $arena)
         {
           $loop = false;
           $block;
           if ($arena->gameState == ArenaState::WAITING)
           {
             if (count($arena->playersInArena) >= $arena->minPlayers)
             {
               $arena->gameState = ArenaState::STARTING;
               $arena->timer = $arena->timeInLobbyUntilStart;
               ArenaHandler::sendFMessage($arena,  ConfigC::normal_lobbyArenaIsStarting, "1-" + $arena->timeInLobbyUntilStart );
             }
           }
           else if ($arena->gameState == ArenaState::STARTING)
           {
             $arena->timer -= 1;
             if ($arena->timer > 0)
             {
               if ($arena->timer == 60)
               {
                 ArenaHandler::sendFMessage(arena, ConfigC::normal_lobbyArenaIsStarting,   "1-60" );
               }
               else if ($arena->timer == 30)
               {
                 ArenaHandler::sendFMessage(arena, ConfigC::normal_lobbyArenaIsStarting,  "1-30" );
               }
               else if ($arena->timer == 10)
               {
                 ArenaHandler::sendFMessage(arena, ConfigC::normal_lobbyArenaIsStarting,  "1-10" );
               }
               else if ($arena->timer == 5)
               {
                 // for (Player pl : $arena->playersInArena) {
                   // pl.playSound(pl.getLocation(),  Sound.ORB_PICKUP, 1, 0);
                 // }
                 ArenaHandler::sendFMessage(arena, ConfigC::normal_lobbyArenaIsStarting, "1-5" );
               }
               else if ($arena->timer == 4)
               {
                 // for (Player pl : $arena->playersInArena) {
                   // pl.playSound(pl.getLocation(), 
                     // Sound.ORB_PICKUP, 1, 0);
                 // }
                 ArenaHandler::sendFMessage(arena, ConfigC::normal_lobbyArenaIsStarting,  "1-4" );
               }
               else if ($arena->timer == 3)
               {
                 // for (Player pl : $arena->playersInArena) {
                   // pl.playSound(pl.getLocation(), 
                     // Sound.ORB_PICKUP, 1, 1);
                 // }
                ArenaHandler::sendFMessage(arena, ConfigC::normal_lobbyArenaIsStarting,  "1-3" );
               }
               else if ($arena->timer == 2)
               {
                 // for (Player pl : $arena->playersInArena) {
                   // pl.playSound(pl.getLocation(), 
                     // Sound.ORB_PICKUP, 1, 1);
                 // }
                 ArenaHandler::sendFMessage(arena, ConfigC::normal_lobbyArenaIsStarting,  "1-2" );
               }
               else if ($arena->timer == 1)
               {
                 // for (Player pl : $arena->playersInArena) {
                   // pl.playSound(pl.getLocation(), 
                     // Sound.ORB_PICKUP, 1, 2);
                 // }
                 ArenaHandler::sendFMessage(arena, ConfigC::normal_lobbyArenaIsStarting,  "1-1" );
               }
             }
             else
             {
               $arena->gameState = ArenaState::INGAME;
               $arena->timer = $arena->gameTime;
               ArenaHandler::sendFMessage(arena, ConfigC::normal_lobbyArenaStarted, "secs-" + $arena->waitingTimeSeeker);
               for ($i = $arena->amountSeekersOnStart; $i > 0; $i--)
               {
                 $loop = true;
                 $seeker = mt_rand(0, (count($arena->playersInArena)-1));
                 foreach($arena->playersInArena as $playerCheck) {
                   if ($this->blockhunt->storage->choosenSeeker[$playerCheck] != null) {
                     if ($this->blockhunt->storage->choosenSeeker[$playerCheck])
                     {
						$seeker = $playerCheck;
						unset($this->blockhunt->storage->choosenSeeker[$playerCheck]);
                     }
                     else if ($seeker == $playerCheck)
                     {
                       $i++;
                       $loop = false;
                     }
                   }
                 }
                 if ($loop) {
                   if (!in_array($seeker, $arena->seekers))
                   {
                     ArenaHandler::sendFMessage($arena, "%TAG%NPlayer %A%seeker%%N has been choosen as seeker!",  "seeker-" + $seeker->getName() );
                     $arena->seekers[] = $seeker;
                     $seeker->teleport($arena->seekersWarp);
                     $seeker.getInventory()->clearAll();
                    $this->blockhunt->storage->seekertime[$seeker] = $arena->waitingTimeSeeker;
                   }
                   else
                   {
                     $i++;
                   }
                 }
               }
               foreach($arena->playersInArena as $arenaPlayer) {
                 if (!in_array($arenaPlayer, $arena->seekers))
                 {
                   $arenaPlayer->getInventory()->clearAll();
				   
                   $block = $arena->disguiseBlocks[mt_rand(0, count($arena->disguiseBlocks))];
                   if ($this->blockhunt->storage->choosenBlock[$arenaPlayer] != null)
                   {
                     $block = $this->blockhunt->storage->choosenBlock[$arenaPlayer];
                     unset($this->blockhunt->storage->choosenBlock[$arenaPlayer]);
                   }
				   
                   DisguiseAPI::disguiseToAll($arenaPlayer, $block->getID());
                   
                   $arenaPlayer->teleport($arena->hidersWarp);
                   
                   $blockCount = Item::get(5);
                   $blockCount->setDurability($block->getMaxDurability());
                   $arenaPlayer.getInventory()->setItem(8, $blockCount);
                   $arenaPlayer.getInventory()->setArmorItem(0, $block);
                   $this->blockhunt->storage->pBlock[$arenaPlayer] = $block;
                   if ($block.getDurability() != 0) {
						MessageM::sendFMessage($arenaPlayer,"%TAG%NYou're disguised as a(n) '%A%block%%N' block.", "block-" + block.getType()->name().replaceAll("_", "").replaceAll("BLOCK", "").toLowerCase() + ":" + block.getDurability() );
                   } else {
						MessageM.sendFMessage($arenaPlayer, ConfigC::normal_ingameBlock, "block-" + $block.getType()->name().replaceAll("_", "").replaceAll("BLOCK", "").toLowerCase() );
                   }
                 }
               }
             }
           }
           foreach($arena->seekers as $player)
           {
             if (($player->getInventory()->getItem(0) == null) || ($player->getInventory()->getItem(0)->getID() != Item::DIAMOND_SWORD))
             {
               $player->getInventory().setItem(0, Item::DIAMOND_SWORD);
               $player->getInventory().setArmorItem(0, Item::IRON_HELMET);
               $player->getInventory().setArmorItem(1, Item::IRON_CHESTPLATE);
               $player->getInventory().setArmorItem(2, Item::IRON_LEGGINGS);
               $player->getInventory().setArmorItem(3, Item::IRON_BOOTS);
               //$player->playSound(player.getLocation(), Sound.ANVIL_USE, 1, 1);
             }
             if ($this->blockhunt->storage->seekertime[$player] != null)
             {
				$this->blockhunt->storage->seekertime[$player] -= 1;
               if ($this->blockhunt->storage->seekertime[$player] <= 0)
               {
                 $player->teleport($arena->hidersWarp);
                 unset($this->blockhunt->storage->seekertime[$player]);
                 ArenaHandler::sendFMessage(arena, ConfigC::normal_ingameSeekerSpawned, "playername-" + player.getName() );
               }
             }
           }
           if ($arena->gameState == ArenaState::INGAME)
           {
             $arena->timer -= 1;
             if ($arena->timer > 0)
             {
               if ($arena->timer == $arena->gameTime - $arena->timeUntilHidersSword)
               {
                 $sword = Item::WOOD_SWORD;
                 //$sword->addUnsafeEnchantment(Enchantment.KNOCKBACK, 1);
                 foreach($arena->playersInArena as $arenaPlayer)
                 {
                   if (!in_array($arenaPlayer, $arena->seekers))
                   {
                     $arenaPlayer->getInventory()->addItem($sword);
                     MessageM.sendFMessage($arenaPlayer, ConfigC::normal_ingameGivenSword);
                   }
                 }
               }
               if ($arena->timer == 190)
               {
                 ArenaHandler::sendFMessage($arena, ConfigC::normal_ingameArenaEnd,  "1-190" );
               }
               else if ($arena->timer == 60)
               {
                 ArenaHandler::sendFMessage($arena, ConfigC::normal_ingameArenaEnd,  "1-60" );
               }
               else if ($arena->timer == 30)
               {
                 ArenaHandler::sendFMessage(arena, 
                   ConfigC::normal_ingameArenaEnd,  "1-30" );
               }
               else if ($arena->timer == 10)
               {
                 ArenaHandler::sendFMessage(arena, ConfigC::normal_ingameArenaEnd,  "1-10" );
               }
               else if ($arena->timer == 5)
               {
                 //$arena->lobbyWarp.getWorld().playSound($arena->lobbyWarp, Sound.ORB_PICKUP, 1, 0);
                 ArenaHandler::sendFMessage($arena, ConfigC::normal_ingameArenaEnd,  "1-5" );
               }
               else if ($arena->timer == 4)
               {
                 //$arena->lobbyWarp.getWorld().playSound($arena->lobbyWarp, Sound.ORB_PICKUP, 1, 0);
                 ArenaHandler::sendFMessage(arena, ConfigC::normal_ingameArenaEnd,  "1-4" );
               }
               else if ($arena->timer == 3)
               {
                 //$arena->lobbyWarp.getWorld().playSound($arena->lobbyWarp, Sound.ORB_PICKUP, 1, 1);
                 ArenaHandler::sendFMessage($arena, ConfigC::normal_ingameArenaEnd,  "1-3" );
               }
               else if ($arena->timer == 2)
               {
                 //$arena->lobbyWarp.getWorld().playSound($arena->lobbyWarp, Sound.ORB_PICKUP, 1, 1);
                 ArenaHandler::sendFMessage($arena, ConfigC::normal_ingameArenaEnd,  "1-2" );
               }
               else if ($arena->timer == 1)
               {
                 $arena->lobbyWarp.getWorld().playSound($arena->lobbyWarp,Sound.ORB_PICKUP, 1, 2);
                 ArenaHandler::sendFMessage($arena, ConfigC::normal_ingameArenaEnd,  "1-1" );
               }
             }
             else
             {
               ArenaHandler::hidersWin(arena);
               return;
             }
             foreach($arena->playersInArena as $arenaPlayer)
             {
               if (!$arena->seekers.contains($arenaPlayer))
               {
                 $pLoc = new Position($arenaPlayer->getX() - 0.5, $arenaPlayer->getY(), $arenaPlayer->getZ() - 0.5, $arenaPlayer->getLevel());
                 $moveLoc = $this->blockhunt->storage->moveLoc[$arenaPlayer];
                 $block = $arenaPlayer->getInventory()->getItem(8);
                 if (($block == null) && ($this->blockhunt->storage->pBlock[$arenaPlayer] != null))
                 {
                   $block = $this->blockhunt->storage->pBlock[$arenaPlayer];
                   $arenaPlayer->getInventory()->setItem(8, $block);
                 }
                 if ($moveLoc != null) {
                   if (($moveLoc->getX() == $pLoc->getX()) && ($moveLoc->getY() == $pLoc->getY()) && ($moveLoc->getZ() == $pLoc->getZ()))
                   {
                     if ($block->getSize() > 1)
                     {
                       $block->setAmount($block->getSize() - 1);
                     }
                     else
                     {
                       $pBlock = $player->getLocation()->getBlock();
                       if (($pBlock->getType()->equals(Item::AIR)) || ($pBlock->getType()->equals(Item::WATER)) || ($pBlock->getType()->equals(Item::STATIONARY_WATER)))
                       {
                         if (($pBlock->getType()->equals(Item::WATER)) || ($pBlock->getType()->equals(Item::STATIONARY_WATER))) {
                           $this->blockhunt->storage->hiddenLocWater.put($player, true);
                         } else {
                           $this->blockhunt->storage->hiddenLocWater.put($player, false);
                         }
                         $arrayOfPlayer = array();
                         if (DisguiseAPI.isDisguised($player))
                         {
                           DisguiseAPI.undisguiseToAll(player);
                           
                           $j = count($arrayOfPlayer = $this->blockhunt->getServer()->getgetOnlinePlayers());
                           for ($i = 0; $i < $j; $i++)
                           {
                             $pl = $arrayOfPlayer[$i];
                             if ($pl->getID() != $player->getID())
                             {
                               $pl->hidePlayer($player);
                               $pl->sendBlockChange($pBlock->getLocation(), $block->getType(), $block->getDurability());
                             }
                           }
                           //$block->addUnsafeEnchantment(Enchantment::DURABILITY, 10);
                           //$player->playSound($pLoc, Sound.ORB_PICKUP, 1, 1);
                           $this->blockhunt->storage->hiddenLoc[$player] = $moveLoc;
                           MessageM.sendFMessage($player, ConfigC::normal_ingameNowSolid, $block->toString() );
                         }
                         $j = count($arrayOfPlayer = $this->blockhunt->getServer()->getgetOnlinePlayers());
                         for ($i = 0; $i < $j; $i++)
                         {
                           $pl = $arrayOfPlayer[$i];
                           if ($pl->getID() != $player->getID())
                           {
                             $pl->hidePlayer($player);
                             $pl->sendBlockChange($pBlock->getLocation(), $block->getType(), $block->getDurability());
                           }
                         }
                       }
                       else
                       {
                         MessageM.sendFMessage($player, ConfigC::warning_ingameNoSolidPlace, "");
                       }
                     }
                   }
                   else
                   {
                     $block->setAmount(5);
                     if (!DisguiseAPI::isDisguised($player)) {
                       SolidBlockHandler::makePlayerUnsolid($player);
                     }
                   }
                 }
               }
             }
           }
           foreach($arena->playersInArena as $arenaPlayer)
           {
             $arenaPlayer->setLevel($arena->timer);
             $arenaPlayer->setGameMode(GameMode::SURVIVAL);
           }
           ScoreboardHandler::updateScoreboard($arena);
         }
         SignsHandler::updateSigns();
	}
}
?>