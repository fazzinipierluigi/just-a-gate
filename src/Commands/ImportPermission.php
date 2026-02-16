<?php

namespace Fazzinipierluigi\JustAGate\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;

class ImportPermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import all permissions';

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
    	$permission_list = [];

		$this->info('> Creating custom permissions');
		$bar = $this->output->createProgressBar(count(config('acl.additional')));
		$bar->start();
		foreach (config('acl.additional') as $key => $name)
		{
			$permission_list[] = $key;
			Artisan::call('permission:create', [
				'key' => $key,
				'name' => $name
			]);
			$bar->advance();
		}
		$bar->finish();
		$this->line('');
		$this->line('');

		$this->info('> Creating route permissions');
		$routeCollection = \Illuminate\Support\Facades\Route::getRoutes();
		foreach ($routeCollection as $route)
		{
			$action = $route->getAction('controller');
			$action = explode('\\', $action);
			$action = $action[count($action)-1];
			$action = str_replace('Controller@', '.', $action);
			$action = strtolower($action);

			$middlewares = $route->getAction('middleware');
			if(is_string($middlewares) || empty($middlewares))
				$middlewares = [$middlewares];
			if(in_array(config('acl.middleware'),$middlewares))
			{
				$permission_list[] = $action;
				Artisan::call('permission:create', [
					'key' => $action
				]);
			}
		}
		$this->line('');

		if(config('acl.role_user_creation'))
		{
			$this->info('> Creating role-based permissions');
			$all_roles = \Fazzinipierluigi\JustAGate\Models\Role::all();
			$bar = $this->output->createProgressBar(count($all_roles));
			$bar->start();
			foreach($all_roles as $role)
			{
				$key = 'user.create.role_'.$role->slug;
				$permission_list[] = $key;
				Artisan::call('permission:create', [
					'key' => $key,
					'name' => 'Can create a user with role "'.$role->name.'"'
				]);
				$bar->advance();
			}
			$bar->finish();
			$this->line('');
			$this->line('');
		}

		if(config('acl.clean_permission'))
		{
			$to_clean = array_diff(\Fazzinipierluigi\JustAGate\Models\Permission::all()->pluck('key')->toArray(), $permission_list);
			if(!empty($to_clean))
			{
				$this->info('> Cleaning permissions');
				$bar = $this->output->createProgressBar(count($to_clean));
				$bar->start();
				foreach($to_clean as $remove)
				{
					$key = strtolower($remove);
					\Fazzinipierluigi\JustAGate\Models\Permission::where('key', '=', $key)->first()->delete();

					$bar->advance();
				}

				$bar->finish();
				$this->line('');
				$this->line('');
			}
		}

		$translations = config('acl.translate');
		if(!empty($translations))
		{
			$this->info('> Translating permissions');
			$bar = $this->output->createProgressBar(count($translations));
			$bar->start();

			foreach($translations as $permission_key => $name_translation)
			{
				$key = strtolower($permission_key);
				$permission = \Fazzinipierluigi\JustAGate\Models\Permission::findByKey($key);
				if(!empty($permission) && $name_translation != $permission->name)
				{
					$permission->name = $name_translation;
					$permission->save();
				}

				$bar->advance();
			}

			$bar->finish();
			$this->line('');
			$this->line('');
		}

		$grants = config('acl.assign');
		if(!empty($grants))
		{
			$npermessi = count(array_filter(Arr::flatten($grants)));
			$this->info('> Assigning permissions');
			$bar = $this->output->createProgressBar($npermessi);
			$bar->start();
			foreach($grants as $permission_key => $roles)
			{
				if(!empty($roles))
				{
					foreach($roles as $role)
					{
						Artisan::call('permission:assign', [
							'key' => $permission_key,
							'role' => $role
						]);
						$bar->advance();
					}
				}
			}
			$bar->finish();
			$this->line('');
			$this->line('');
		}

        return Command::SUCCESS;
    }
}
