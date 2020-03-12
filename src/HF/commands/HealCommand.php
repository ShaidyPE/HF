<?php

namespace HF\commands;

use pocketmine\command\{Command, CommandSender};

use pocketmine\Player;

class HealCommand extends Command
{
	/** @var \HF\Main $pg */
	private $pg;

	/**
	 * @param \HF\Main $pg
	*/
	public function __construct(\HF\Main $pg){
		$this->pg = $pg;

		parent::__construct("heal", "change health for player");

		$this->setPermission("shaidype.health.use");
	}

	/**
	 * @param CommandSender $s
	 * @param string $label
	 * @param array $args
	 *
	 * @return bool result
	*/
	public function execute(CommandSender $s, string $label, array $args): bool{
		if(!$s instanceof Player){
			$s->sendMessage("Only for players!");
		}else{
			$this->pg->sendForm($s, "health");
		}

		return true;
	}
}