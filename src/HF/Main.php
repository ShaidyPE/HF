<?php

namespace HF;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\Player;

use pocketmine\utils\TextFormat;

use pocketmine\entity\Attribute;

use pocketmine\network\mcpe\protocol\{PacketPool, UpdateAttributesPacket};

use HF\commands\FoodCommand;
use HF\commands\HealCommand;

use pocketmine\event\server\DataPacketReceiveEvent;

//libs for forms
use HF\customui\network\{ModalFormRequestPacket, ModalFormResponsePacket};

use HF\customui\windows\CustomForm;

use HF\customui\elements\{Dropdown, Slider};

class Main extends PluginBase implements Listener
{
	/** @var array $playerList */
	private $playerList = [];

	/** VOID */
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		//commands registration
		$this->getServer()->getCommandMap()->register("food", new FoodCommand($this));
		$this->getServer()->getCommandMap()->register("heal", new HealCommand($this));

		$this->saveResource("config.yml");

		PacketPool::registerPacket(new ModalFormRequestPacket());
		PacketPool::registerPacket(new ModalFormResponsePacket());
	}

	/**
	 * @param DataPacketReceiveEvent $ev
	 *
	 * @return bool result
	*/
	public function onReceivePacket(DataPacketReceiveEvent $ev): bool{
		if($ev->getPacket() instanceof ModalFormResponsePacket){
			/** @var int $formId */
			$formId = $ev->getPacket()->formId;

			/** @var string $formData */
			$formData = trim($ev->getPacket()->formData);

			if($formData === "null") return false;

			if($formId === 120 or $formId === 121){
				/** @var int $data */
				$data = preg_replace("/[^\d.,]+/", "", $ev->getPacket()->formData);

				/** @var array $data */
				$data = explode(",", $data);

				/** @var Player $p */
				$p = $this->getServer()->getPlayer($this->playerList[$ev->getPlayer()->getName()][$data[0]]);

				if(!is_null($p)){
					if($formId === 120){
						$this->updateAttribute($p, Attribute::FOOD, [0, $this->getConfig()->get("max_food"), (int) $data[1]]);

						$p->sendMessage($this->getConfig()->get("player_food"));

						$ev->getPlayer()->sendMessage($this->getConfig()->get("sender_food"));
					}else{
						$this->updateAttribute($p, Attribute::HEALTH, [0, $this->getConfig()->get("max_health"), (int) $data[1]]);

						$p->sendMessage($this->getConfig()->get("player_health"));

						$ev->getPlayer()->sendMessage($this->getConfig()->get("sender_health"));
					}
				}else{
					$ev->getPlayer()->sendMessage(TextFormat::RED ."Player not found!");
				}
			}

			unset($this->playerList[$ev->getPlayer()->getName()]);
		}

		return true;
	}

	/**
	 * @param Player $p
	 * @param string $category
	*/
	public function sendForm(Player $p, string $category): void{
		switch($category):
			case "food":
				/** @var CustomForm $form */
				$form = new CustomForm(TextFormat::BOLD ."Food");

				foreach($this->getServer()->getOnlinePlayers() as $pl){
					$this->playerList[$p->getName()][] = $pl->getName();
				}

				$form->addElement(new Dropdown("Change player", $this->playerList[$p->getName()]));

				$form->addElement(new Slider("Change food", 1, $this->getConfig()->get("max_food"), 1));

				/** @var ModalFormRequestPacket $pk */
				$pk = new ModalFormRequestPacket();

				$pk->formId = 120;
				$pk->formData = json_encode($form);

				$p->sendDataPacket($pk);
			break;
			case "health":
				/** @var CustomForm $form */
				$form = new CustomForm(TextFormat::BOLD ."Health");

				foreach($this->getServer()->getOnlinePlayers() as $pl){
					$this->playerList[$p->getName()][] = $pl->getName();
				}

				$form->addElement(new Dropdown("Change player", $this->playerList[$p->getName()]));

				$form->addElement(new Slider("Change health", 1, $this->getConfig()->get("max_health"), 1));

				/** @var ModalFormRequestPacket $pk */
				$pk = new ModalFormRequestPacket();

				$pk->formId = 121;
				$pk->formData = json_encode($form);

				$p->sendDataPacket($pk);
			break;
		endswitch;
	}

	/**
	 * @param Player $p
	 * @param int $attributeId
	 * @param int[] $options
	*/
	public function updateAttribute(Player $p, int $attributeId, array $options): void{
		/** @var Attribute $attr */
		$attr = $p->getAttributeMap()->getAttribute($attributeId);

		$attr->setMinValue($options[0]);
		$attr->setMaxValue($options[1]);

		$attr->setValue($options[2]);

		/** @var UpdateAttributesPacket $pk */
		$pk = new UpdateAttributesPacket();

		$pk->entityRuntimeId = $p->getId();
		$pk->entries[] = $attr;

		$p->sendDataPacket($pk);
	}
}