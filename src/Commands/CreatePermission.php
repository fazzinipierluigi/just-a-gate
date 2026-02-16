<?php

namespace Fazzinipierluigi\JustAGate\Commands;

use Illuminate\Console\Command;
use function Laravel\Prompts\text;

class CreatePermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:create {key? : La chiave del nuovo permesso} {name? : Il nome da visualizzare per il nuovo permesso}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new permission if it does not exist';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
    	$key = $this->argument('key');
    	if(empty($key))
		{
			$key = text(
				label: 'Permission key',
				validate: fn (string $value) => match (true) {
					empty(trim($value)) => 'The key cannot be empty',
					strlen(trim($value)) > 3 => 'The key must be longer than 3 characters',
					default => null
				}
			);
		}

		$key = strtolower($key);
		if(\Fazzinipierluigi\JustAGate\Models\Permission::where('key', '=', $key)->count() == 0)
		{
			$permission = new \Fazzinipierluigi\JustAGate\Models\Permission();
			$permission->key = $key;

			$name = trim($this->argument('name'));
			if(!empty($name))
				$permission->name = $name;

			$permission->save();

			$this->info('Created permission ['.$key.']');
		}
		else
		{
			$this->error('Permission already exists');
            return Command::FAILURE;
		}

    	$this->line('');
        return Command::SUCCESS;
    }
}
