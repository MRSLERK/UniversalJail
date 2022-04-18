<?php namespace universal\jail\command;



use universal\jail\Manager;

use universal\jail\util\Declinator;

use universal\jail\object\Account;





use pocketmine\command\CommandSender;

use pocketmine\command\Command;



use pocketmine\permission\Permission;

use pocketmine\player\Player;

use pocketmine\world\Position;



class JailCommand extends Command

{

	/**
	 * @var Manager

	 */

	private $main;



	/**

	 * @var int[]

	 */

	private $use_counter = [];

	

	const NAME        = 'jail';

	const DESCRIPTION = 'Посадить игрока в тюрьму.';

	

	/**

	 * @override

	 *

	 * @param string $name

	 * @param string $description

	 */

	function __construct(Manager $main, string $name = self::NAME, string $description = self::DESCRIPTION )

	{

		parent::__construct($name, $description);

		$this->main = $main;

	}



	/**

	 * @param  CommandSender $sender

	 * @param  string        $label

	 * @param  string[]      $argument

	 *

	 * @return bool

	 */

	function execute( CommandSender $sender, string $label, array $argument )

	{

		$main = $this->getManager();

		$prefix = Manager::SERVER_NAME;

		if( $sender instanceof Player )

		{

			$name = $sender->getName();



			$manager = $main->getUniGroup();

			$level   = Manager::ACCESS_COMMAND_JAIL;



			if( !$manager->hasAccess($name, $level) )

			{

				$group = $manager->getGroup($level);



				$sender->sendMessage("§c< §f$prefix §c> Требуется доступ группы $group!");

				$sender->sendMessage("§c< /h §f- узнать подробнее.");

				return false;

			}

		}



		//////////////////////////////////////////////////



		if( count($argument) < 1 )

		{

			$sender->sendMessage("§e< §f/jail <игрок> [срок_дн] [причина...]");

			return false;

		}



		$nick = array_shift($argument);



		if( strlen($nick) > 16 )

		{

			$sender->sendMessage("§c< §f$prefix §c> Никнейм не является действительным!");

			return false;

		}

		

		if( isset($name) and strtolower($nick) == strtolower($name) )

		{

			$sender->sendMessage("§c< §f$prefix §c> Вы не можете ппосадить в тюрьму сами себя!");

			return false;

		}



		if( $sender instanceof Player )

		{

			$shield = Manager::SHIELD_DIFFERENCE_COMMAND_JAIL;

			$level  = $manager->getLevel($name) + $shield;



			if( $manager->getLevel($nick) > $level )

			{

				$group = $manager->getGroup($manager->getLevel($nick) - $shield);



				$sender->sendMessage("§c< §f$prefix §c> Указанного игрока может посадить в тюрьму $group!");

				$sender->sendMessage("§c< /h §f- узнать подробнее.");

				return false;

			}

		}



		//////////////////////////////////////////////////



		$jail = $main->getProvider()->get($nick);

	if($jail)

		{

			$sender->sendMessage("§c< §f$prefix §c> $nick уже находится в тюрьме!");

			return false;

		}



		//////////////////////////////////////////////////



		$limit  = Manager::LIMIT_EXPIRE_COMMAND_JAIL;

		$expire = $limit;



		if( !($sender instanceof Player) )

		{

			$expire = 24;

		}



		if( count($argument) > 0 )

		{

			$expire = array_shift($argument);

		}



		if( !is_numeric($expire) )

		{

			$sender->sendMessage("§c< §f$prefix §c> Срок не является числом!");

			return false;

		}



		$expire = intval($expire);



		if( $expire < 1 )

		{

			$sender->sendMessage("§c< §f$prefix §c> Срок не может быть меньше 1 часа!");

			return false;

		}



		if( $expire > $limit and $sender instanceof Player )

		{

			$level = Manager::ACCESS_IGNORE_LIMIT_EXPIRE_COMMAND_JAIL;



			if( !$manager->hasAccess($name, $level) )

			{

				$sender->sendMessage("§c< §f$prefix §c> Максимальный срок ограничен!");

				$sender->sendMessage("§c< $limit §fчасов для Вашей группы.");

				return false;

			}

		}



		if( $expire > 24 )

		{

			$expire = 24;

		}



		//////////////////////////////////////////////////



		if( isset($name) and !$this->handleUse($name) )

		{

			$limit = Manager::LIMIT_COMMAND_JAIL;



			$sender->sendMessage("§c< §f$prefix §c> Количество ииспользование команды ограничено!");

			$sender->sendMessage("§c< $limit §fдля Вашей группы.");

			return false;

		}



		//////////////////////////////////////////////////



		$reason = '<не указано>';



		if( !($sender instanceof Player) )

		{

			$reason = '<решение администрации>';

		}



		if( count($argument) > 0 )

		{

			$reason = implode(' ', $argument);

		}



		//////////////////////////////////////////////////



		$name    = $name ?? 'Сервер';

		$player  = $main->getServer()->getPlayerExact($nick) ?? $nick;

		$expire *= 3600;



		$main->getProvider()->add($nick, $expire, $reason, $name);



		//////////////////////////////////////////////////



		$expire = Declinator::formDate($expire);

		$target = $main->getServer()->getPlayerExact($nick);

		if( isset($target) )

		{

		    $main->setLastPosition($target->getName(), $target->getPosition());

		    $center = Manager::JAIL_POS;

		$center = new Position($center[0], $center[1], $center[2], $target->getWorld());

		$main->teleport($target, $center);

		    

		    $target->sendMessage(

				"§c< §f$prefix §c> Вас посадили в тюрьму!". PHP_EOL.

				"§c< Причина: §f$reason".              PHP_EOL.

				"§c< Срок: §f$expire"

			);

		}

		if( $sender instanceof Player )

		{

			$main->getServer()->broadcastMessage(

				"§e< §f$prefix §e> $name посадил(а) в тюрьму игрока $nick!". PHP_EOL.

				"§e< Причина: §f$reason".                        PHP_EOL.

				"§e< Срок: §f$expire"

			);

		}



		else

		{

			$sender->sendMessage(

				"§a< §f$prefix §a> Игрок $nick посажен!". PHP_EOL.

				"§a< Причина: §f$reason".              PHP_EOL.

				"§a< Срок: §f$expire"

			);

		}



		return true;

	}





	/**

	 * @param  string $nick

	 *

	 * @return bool

	 */

	private function handleUse( string $nick )

	{

		$nick = strtolower($nick);



		if( !isset($this->use_counter[$nick]) )

		{

			$this->use_counter[$nick] = 0;

		}



		$limit = Manager::LIMIT_COMMAND_JAIL;



		if( $this->use_counter[$nick]++ < $limit )

		{

			return true;

		}



		$level = Manager::ACCESS_IGNORE_LIMIT_COMMAND_JAIL;



		if( $this->getManager()->getUniGroup()->hasAccess($nick, $level) )

		{

			return true;

		}



		return false;

	}





	/**

	 * @return Manager

	 */

	private function getManager( )

	{

		return $this->main;

	}

}

//}
