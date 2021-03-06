<?php

namespace App\Admin\Controllers;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Controllers\AdminController;

class AdminUserController extends AdminController
{
    /**
     * {@inheritdoc}
     */
    protected function title()
    {
        return trans('admin.administrator');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        
        $userModel = config('admin.database.users_model');
        
        $grid = new Grid(new $userModel());

        $grid->model()->latest();

        //$grid->column('id', 'ID')->sortable();

        $grid->column('username', trans('admin.username'));

        $grid->column('operate', '操盘号');

        $grid->column('name', trans('admin.name'));

        $grid->column('roles', trans('admin.roles'))->pluck('name')->label();

        $grid->column('created_at', trans('admin.created_at'));

        $grid->column('updated_at', trans('admin.updated_at'));

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            if ($actions->getKey() == 1) {
                $actions->disableDelete();
            }
        });

        $grid->tools(function (Grid\Tools $tools) {
            $tools->batch(function (Grid\Tools\BatchActions $actions) {
                $actions->disableDelete();
            });
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        $userModel = config('admin.database.users_model');
        // if(Admin::user()->operate != "All" && request()->route()->parameters()){
        //     $userModels = $userModel::where('id', request()->route()->parameters()['admin_users'])->first();
        //     if($userModels->operate != Admin::user()->operate) return abort('403'); 
        // }
        $show = new Show($userModel::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('username', trans('admin.username'));

        $show->field('operate', '操盘号');

        $show->field('name', trans('admin.name'));
        $show->field('roles', trans('admin.roles'))->as(function ($roles) {
            return $roles->pluck('name');
        })->label();
        $show->field('permissions', trans('admin.permissions'))->as(function ($permission) {
            return $permission->pluck('name');
        })->label();
        $show->field('created_at', trans('admin.created_at'));
        $show->field('updated_at', trans('admin.updated_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function form()
    {
        
        $userModel = config('admin.database.users_model');
        $permissionModel = config('admin.database.permissions_model');
        $roleModel = config('admin.database.roles_model');

        $form = new Form(new $userModel());

        $userTable = config('admin.database.users_table');
        $connection = config('admin.database.connection');

        $form->display('id', 'ID');
        $form->text('username', trans('admin.username'))
            ->creationRules(['required', "unique:{$connection}.{$userTable}"])
            ->updateRules(['required', "unique:{$connection}.{$userTable},username,{{id}}"]);

        $form->text('name', trans('admin.name'))->rules('required');

        $form->text('operate', '操盘号')->value("CP100".date("YmdHis").rand(10,99))->rules('required')->readonly();

        $form->image('avatar', trans('admin.avatar'));

        $form->password('password', trans('admin.password'))->rules('required|confirmed');

        $form->password('password_confirmation', trans('admin.password_confirmation'))->rules('required')
            ->default(function ($form) {
                return $form->model()->password;
            });

        $form->ignore(['password_confirmation']);

        $form->multipleSelect('roles', trans('admin.roles'))->options($roleModel::all()->pluck('name', 'id'));

        $form->multipleSelect('permissions', trans('admin.permissions'))->options($permissionModel::all()->pluck('name', 'id'));

        $form->display('created_at', trans('admin.created_at'));
        $form->display('updated_at', trans('admin.updated_at'));

        $form->saving(function (Form $form) {
            if ($form->password && $form->model()->password != $form->password) {

                /**
                 * @version [<vector>] [< 如果是新增操盘方>]
                 */
                if($form->isCreating())
                {
                    \App\User::create([
                        'nickname'  =>  $form->username,
                        'account'   =>  $form->username,
                        'password'  =>  "###" . md5(md5($form->password . 'v3ZF87bMUC5MK570QH')),
                        'user_group'=>  1,
                        'operate'   =>  $form->operate,
                    ]);
                }

                $form->password = bcrypt($form->password);
            }
        });

        return $form;
    }
}
