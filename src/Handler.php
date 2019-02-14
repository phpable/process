<?php
namespace Able\Process;

use \Able\Prototypes\IExecutable;

use \Able\Helpers\Arr;
use \Able\Helpers\Str;

class Handler implements IExecutable {

	/**
	 * @var array
	 */
	private $Environment = [];

	/**
	 * @param string $name
	 * @param string $value
	 */
	public final function setEnvironment(string $name, string $value): void {
		$this->Environment[$name] = $value;
	}

	/**
	 * @var int
	 */
	private $mode = 0;

	/**
	 * @const int
	 */
	const MD_OUTPUT_STD = 0b0001;

	/**
	 * @const int
	 */
	const MD_OUTPUT_ERR = 0b0010;

	/**
	 * @var string
	 */
	private $content = null;

	/**
	 * @param string $command
	 * @param int $mode
	 * @throws \Exception
	 */
	public function __construct(string $command, int $mode = self::MD_OUTPUT_STD) {
		if (!preg_match('/^\w{2,}/', $command)) {
			throw new \Exception(sprintf('Invalid command syntax: %s!', $command));
		}

		$this->content = $command;

		if ($mode > (self::MD_OUTPUT_STD | self::MD_OUTPUT_ERR)) {
			throw new \Exception(sprintf('Unsipported mode: %s!', Str::lpad(decbin($mode), '0', 4)));
		}

		$this->mode = $mode;
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	public final function execute(): string {
		$dh = proc_open($this->content, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $Pipes, null, $this->Environment);

		try {
			if (!is_resource($dh)) {
				throw new \Exception('Process initiation failed!');
			}

			foreach(Arr::simplify(func_get_args()) as $input) {
				fwrite($Pipes[0], $input);
			}

			fclose($Pipes[0]);

			return  Str::join("\n",
				$this->mode & self::MD_OUTPUT_STD ? stream_get_contents($Pipes[1]) : null,
				$this->mode & self::MD_OUTPUT_ERR ? stream_get_contents($Pipes[2]) : null
			);
		} finally {
			proc_close($dh);
		}
	}
}
