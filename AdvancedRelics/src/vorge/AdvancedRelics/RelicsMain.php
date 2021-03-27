<?php

namespace vorge\AdvancedRelics;

use onebone\economyapi;
use DaPigGuy\PiggyCustomEnchants\CustomEnchants\CustomEnchants;
use DaPigGuy\PiggyCustomEnchants\Main as CE;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use function array_fill;
use function array_merge;
use function array_rand;
use function is_null;
use function mt_rand;
use function str_replace;

class RelicsMain extends PluginBase{

	/** @var CE */
	private $piggyCE;

	public function onEnable(){
		$this->saveDefaultConfig();
		$this->piggyCE = $this->getServer()->getPluginManager()->getPlugin("PiggyCustomEnchants");
		$this->getServer()->getPluginManager()->registerEvents(new RelicsListener(($this)), $this);
		$this->getLogger()->info("§aPlugin Enabled. Provided by §bvorge");
	}

	public function getTypes(){
		$relics = $this->getConfig()->get("relics");

		$types = [];
		foreach($relics as $type => $data){
			$types[] = $type;
		}

		return $types;
	}

	public function getChances(){
		$relics = $this->getRelics();

		$relic = [];
		foreach($relics as $type => $data){
			$chance = $data["chance"];
			$relic[$type] = $chance;
		}

		return $relic;
	}

	public function getRelics(){
		$types = [];

		foreach($this->getConfig()->get("relics") as $type => $data){
			$types[$type] = $data;
		}

		return $types;
	}

	/**
	 * @return mixed
	 */
	public function getRandomRelic(){
		$relics = $this->getRelics();

		$randomRelics = [];
		foreach($relics as $type => $value){
			if(isset($value["chance"])){
				$chance = $value["chance"];
				$randomRelics = array_merge($randomRelics, array_fill(0, $chance, $type));
			}
		}

		$relic = array_rand($randomRelics, 1);

		return $randomRelics[$relic];
	}

	/**
	 * @param Player $player
	 * @param        $relic
	 * @return bool|Item|null
	 */
	public function reward(Player $player, $relic){
		$data = $this->getRelicData($relic);

		foreach($data["rewards"] as $reward){
			$chance = $reward["chance"];
			$random = mt_rand(1, 100);

			if($random <= $chance){
				if(isset($reward["item"])){
					$item = Item::fromString($reward["item"])->setCount($reward["amount"]);

					if(isset($reward["name"])){
						$item->setCustomName($reward["name"]);
					}

					if(isset($reward["enchantments"])){
						foreach($reward["enchantments"] as $enchantment => $enchantmentinfo){
							$level = $enchantmentinfo["level"];
							$ce = $this->piggyCE;
							if(!is_null($ce) && !is_null($enchant = CustomEnchants::getEnchantmentByName($enchantment))){
								if($ce instanceof CE){
									$item = $ce->addEnchantment($item, $enchantment, $level, false);
								}
							}else{
								if(!is_null($enchant = Enchantment::getEnchantmentByName($enchantment))){
									$item->addEnchantment(new EnchantmentInstance($enchant, $level));
								}
							}
						}
					}

					if(isset($reward["lore"])){
						$item->setLore($reward["lore"] ?? []);
					}

					return $item;
				}

				if(isset($reward["commands"])){
					foreach($reward["commands"] as $command){
						$c = str_replace(["%PLAYER%"], [$player->getName()], $command);
						$this->getServer()->dispatchCommand(new ConsoleCommandSender(), $c);
					}

					return true;
				}
			}
		}

		return null;
	}

	/**
	 * @param $type
	 * @return mixed
	 */
	public function getRelicData($type){
		$relicData = $this->getConfig()->getNested("relics." . $type);

		return $relicData;
	}
} 
