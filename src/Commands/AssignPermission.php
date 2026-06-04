<?php

namespace Fazzinipierluigi\JustAGate\Commands;

use Illuminate\Console\Command;
use function Laravel\Prompts\select;

class AssignPermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:assign {key? : The permission key} {role? : Role slug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign a permission to a role';

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
    public function handle(): int
    {
		$key = trim($this->argument('key'));
		$role_slug = trim($this->argument('role'));

		if(empty($key))
		{
			$key = select(
				label: 'Select the permission to associate',
				options: \Fazzinipierluigi\JustAGate\Models\Permission::pluck('key'),
				scroll: 10,
				validate: function($value) {
					if(!\Fazzinipierluigi\JustAGate\Models\Permission::where('key', '=', $value)->exists())
						return 'Permission does not exist';

					return NULL;
				}
			);
		}

		if(empty($role_slug))
		{
			$role_slug = select(
				label: 'Select the role to associate',
				options: \Fazzinipierluigi\JustAGate\Models\Role::pluck('name', 'slug'),
				scroll: 10,
				validate: function($value) {
					if(!\Fazzinipierluigi\JustAGate\Models\Role::where('slug', '=', $value)->exists())
						return 'Role does not exist';

					return NULL;
				}
			);
		}

		$permission = \Fazzinipierluigi\JustAGate\Models\Permission::where('key', '=', $key)->first();
		if(!empty($permission))
		{
			$role = \Fazzinipierluigi\JustAGate\Models\Role::where('slug', '=', $role_slug)->first();
			if(!empty($role))
			{
				if(\Fazzinipierluigi\JustAGate\Models\PermissionRole::where('permission_id', '=', $permission->id)->where('role_id', '=', $role->id)->count() == 0)
				{
					$relationship = new \Fazzinipierluigi\JustAGate\Models\PermissionRole();
					$relationship->permission_id = $permission->id;
					$relationship->role_id = $role->id;
					$relationship->save();

					$this->info('Permission ['.$permission->key.'] and role ['.$role->slug.'] have been successfully associated');
				}
				else
				{
					$this->error('Permission ['.$permission->key.'] and role ['.$role->slug.'] are already associated');
                    return Command::FAILURE;
				}
			}
			else
			{
				$this->error('Role not found');
                return Command::FAILURE;
			}
		}
		else
		{
			$this->error('Permission not found');
            return Command::FAILURE;
		}

		$this->line('');
        return Command::SUCCESS;
    }
}
