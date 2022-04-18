<?php namespace universal\jail;





/**

 * @author MRSLERK

 * @link   https://vk.com/ii.gor32

 *

 */

use universal\jail\command\JailCommand;

use universal\jail\command\UnJailCommand;

use universal\jail\data\Provider;

use universal\jail\listener\player\JoinListener;

use universal\jail\listener\block\BreakListener;

use universal\jail\listener\block\PlaceListener;

use universal\jail\listener\player\QuitListener;

use universal\jail\listener\player\CommandListener;

use universal\jail\task\PositionCheckTask;



use pocketmine\world\Position;

use pocketmine\player\Player;

use pocketmine\plugin\PluginBase;

use Exception;



class Manager extends PluginBase

{

    

	const ACCESS_COMMAND_JAIL = 100;//130;
	const ACCESS_COMMAND_UNJAIL = 130;

	const LIMIT_COMMAND_JAIL  = 1;

	const LIMIT_COMMAND_UNJAIL  = 1;

	const LIMIT_EXPIRE_COMMAND_JAIL = 3; // ч.

	const ACCESS_IGNORE_LIMIT_COMMAND_JAIL = 130;

	const ACCESS_IGNORE_LIMIT_COMMAND_UNJAIL = 130;

	const ACCESS_IGNORE_LIMIT_EXPIRE_COMMAND_JAIL  = 130;

	const ACCESS_IGNORE_LIMIT_EXPIRE_COMMAND_UNJAIL  = 130;

	const SHIELD_DIFFERENCE_COMMAND_JAIL  = 20;

	const SERVER_NAME = "UKRAINE";

	const LINK = "сылка";

    const JAIL_POS = [83, 67, 160];



	const JAIL_RADIUS = 16;

	

	const BAN_COMMAND_IN_JAIL = [

	    'spawn',

	    'tp',

	    'teleport'

	    ];



	/**

	 * @var Manager

	 */

	private static $instance;

	

	private $unigroup;

	

	/**

	 * @var Position[]

	 */

	private $last_position;

	

	/**

	 * @return Manager

	 */

	static function getInstance( ): Manager

	{

		return self::$instance;

	}

	

	/**

	 * @var Provider

	 */

	private $provider;





	function onEnable( ): void

	{

		$this->loadInstance();

		$this->loadProvider();

		$this->loadUniGroup();

		$this->loadListener();

		$this->loadTask();

		$this->loadCommand();

	}

	function getUniGroup( )

	{

		return $this->unigroup;

	}

	

	private function loadTask( )

	{

		new PositionCheckTask($this);

	}

	

	private function loadUniGroup( )

	{

		$manager = $this->getServer()->getPluginManager()->getPlugin('UniversalGroup');



		if( !isset($manager) )

		{

			throw new Exception('Dependency error: UniversalGroup not found.');

		}



		$this->unigroup = $manager;

	}

	private function loadInstance( )

	{

		self::$instance = $this;

	}





	private function loadProvider( )

	{

		$this->provider = new Provider($this);

	}





	private function loadListener( )

	{

		$list = [

			new JoinListener(),

			new QuitListener($this),

			new PlaceListener($this),

			new BreakListener($this),

			new CommandListener($this)

		];



		foreach( $list as $listener )

		{

			$this->getServer()->getPluginManager()->registerEvents($listener, $this);

		}

	}





	private function loadCommand( )

	{

		$list = [

			new JailCommand($this),

			new UnJailCommand($this)

		];



		foreach( $list as $command )

		{

			$map     = $this->getServer()->getCommandMap();

			$replace = $map->getCommand($command->getName());



			if( isset($replace) )

			{

				$replace->setLabel('');

				$replace->unregister($map);

			}



			$map->register($this->getName(), $command);

		}

	}





	/**

	 * @return Provider

	 */

	 function getProvider( )

	{

		return $this->provider;

	}



	/**

	 * @param Player   $player

	 * @param Position $pos

	 */

	function teleport( Player $player, Position $pos )

	{

		$x     = $pos->getX();

		$z     = $pos->getZ();

		$level = $pos->getWorld();

		$level->loadChunk($x, $z);

		$player->teleport($pos);

	}

	

	function getLastPosition( string $nick, bool $need_unset = false )

	{

		$nick = strtolower($nick);



		if( !isset($this->last_position[$nick]) )

		{

			return null;

		}



		$pos = $this->last_position[$nick];



		if( $need_unset )

		{

			unset($this->last_position[$nick]);

		}



		return $pos;

	}





	/**

	 * @param string   $nick

	 * @param Position $pos

	 */

	function setLastPosition( string $nick, Position $pos )

	{

		$nick = strtolower($nick);



		$this->last_position[$nick] = new Position($pos->getX(), $pos->getY(), $pos->getZ(), $pos->getWorld());

	}



	/**

	 * @return Position

	 */

	function getJailPosition( )

	{

	$vector = self::JAIL_POS;

	$world = $this->getServer()->getWorldManager()->getDefaultWorld();



		return new Position($vector[0], $vector[1], $vector[2], $world);

	}

}
