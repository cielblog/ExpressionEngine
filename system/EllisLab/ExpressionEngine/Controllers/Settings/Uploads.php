<?php

namespace EllisLab\ExpressionEngine\Controllers\Settings;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

use EllisLab\ExpressionEngine\Library\CP;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Uploads Directories Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Uploads extends Settings {

	public function index()
	{
		if ( ! $this->cp->allowed_group('can_admin_upload_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->model('file_upload_preferences_model');

		$upload_dirs = ee()->file_upload_preferences_model->get_file_upload_preferences(
			ee()->session->userdata('group_id')
		);

		$data = array();
		foreach ($upload_dirs as $id => $dir)
		{
			$data[] = array(
				$dir['id'],
				htmlentities($dir['name'], ENT_QUOTES),
				array('toolbar_items' => array(
					'view' => array(
						'href' => cp_url(''),
						'title' => lang('upload_btn_view')
					),
					'edit' => array(
						'href' => cp_url('settings/uploads/edit/'.$dir['id']),
						'title' => lang('upload_btn_edit')
					),
					'sync' => array(
						'href' => cp_url(''),
						'title' => lang('upload_btn_sync')
					)
				)),
				array(
					'name' => 'uploads[]',
					'value' => $dir['id']
				)
			);
		}

		$table = CP\Table::create(array('autosort' => TRUE));
		$table->setColumns(
			array(
				'upload_id' => array(
					'type'	=> CP\Table::COL_ID
				),
				'upload_name',
				'upload_manage' => array(
					'type'	=> CP\Table::COL_TOOLBAR
				),
				array(
					'type'	=> CP\Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_upload_directories', 'create_upload_directory', cp_url('settings/uploads/new-upload'));
		$table->setData($data);

		$base_url = new CP\URL('settings/uploads', ee()->session->session_id());
		$vars['table'] = $table->viewData($base_url);

		$pagination = new CP\Pagination(
			$vars['table']['limit'],
			$vars['table']['total_rows'],
			$vars['table']['page']
		);
		$vars['pagination'] = $pagination->cp_links($vars['table']['base_url']);

		ee()->view->cp_page_title = lang('upload_directories');
		ee()->view->table_heading = lang('all_upload_dirs');

		ee()->cp->set_breadcrumb(cp_url('files'), lang('file_manager'));

		ee()->cp->render('settings/uploads', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * New upload destination
	 */
	public function newUpload()
	{
		return $this->form();
	}

	// --------------------------------------------------------------------

	/**
	 * Edit upload destination
	 *
	 * @param int	$upload_id	Table name, used when coming from SQL Manager
	 *                      	for proper page-naming and breadcrumb-setting
	 */
	public function edit($upload_id)
	{
		return $this->form($upload_id);
	}

	// --------------------------------------------------------------------

	/**
	 * Edit upload destination
	 *
	 * @param int	$upload_id	ID of upload destination to edit
	 */
	private function form($upload_id = NULL)
	{
		// Get the upload directory
		$upload_dir = array();
		if ( ! empty($upload_id))
		{
			ee()->load->model('file_upload_preferences_model');
			$upload_dir = ee()->file_upload_preferences_model->get_file_upload_preferences(
				ee()->session->userdata('group_id'),
				$upload_id
			);
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'upload_name',
					'desc' => 'upload_name_desc',
					'fields' => array(
						'name' => array(
							'type' => 'text',
							'value' => (isset($upload_dir['name'])) ? $upload_dir['name'] : ''
						)
					)
				),
				array(
					'title' => 'upload_url',
					'desc' => 'upload_url_desc',
					'fields' => array(
						'avatar_path' => array(
							'type' => 'text',
							'value' => (isset($upload_dir['url'])) ? $upload_dir['url'] : 'http://'
						)
					)
				),
				array(
					'title' => 'upload_path',
					'desc' => 'upload_path_desc',
					'fields' => array(
						'avatar_path' => array(
							'type' => 'text',
							'value' => (isset($upload_dir['server_path'])) ? $upload_dir['server_path'] : ''
						)
					)
				),
				array(
					'title' => 'upload_allowed_types',
					'desc' => '',
					'fields' => array(
						'avatar_path' => array(
							'type' => 'dropdown',
							'choices' => array(
								'images' => lang('upload_allowed_types_opt_images'),
								'all' => lang('upload_allowed_types_opt_all')
							),
							'value' => (isset($upload_dir['allowed_types'])) ? $upload_dir['allowed_types'] : 'images'
						)
					)
				)
			),
			'file_limits' => array(
				array(
					'title' => 'upload_file_size',
					'desc' => 'upload_file_size_desc',
					'fields' => array(
						'avatar_max_width' => array(
							'type' => 'text',
							'value' => (isset($upload_dir['max_size'])) ? $upload_dir['max_size'] : ''
						)
					)
				),
				array(
					'title' => 'upload_image_width',
					'desc' => 'upload_image_width_desc',
					'fields' => array(
						'avatar_max_height' => array(
							'type' => 'text',
							'value' => (isset($upload_dir['max_width'])) ? $upload_dir['max_width'] : ''
						)
					)
				),
				array(
					'title' => 'upload_image_height',
					'desc' => 'upload_image_height_desc',
					'fields' => array(
						'avatar_max_kb' => array(
							'type' => 'text',
							'value' => (isset($upload_dir['max_height'])) ? $upload_dir['max_height'] : ''
						)
					)
				)
			)
		);

		$grid = CP\GridInput::create(array(
			'field_name' => 'image_manipulations',
			'reorder' => FALSE // Order doesn't matter here
		));
		$grid->setColumns(
			array(
				'image_manip_name' => array(
					'desc'  => 'image_manip_name_desc'
				),
				'image_manip_type' => array(
					'desc'  => 'image_manip_type_desc'
				),
				'image_manip_width' => array(
					'desc'  => 'image_manip_width_desc'
				),
				'image_manip_height' => array(
					'desc'  => 'image_manip_height_desc'
				)
			)
		);
		$grid->setNoResultsText('no_manipulations', 'add_manipulation');
		$grid->setBlankRow(array(
			form_input('name'),
			form_dropdown(
				'type',
				array(
					'constrain' => lang('image_manip_type_opt_constrain'),
					'crop' => lang('image_manip_type_opt_crop'),
				)
			),
			form_input('width'),
			form_input('height')
		));

		// Temporary for testing
		$grid->setData(array(
			array(
				'attrs' => array('row_id' => 20),
				'columns' => array(
					form_input('name'),
					form_dropdown(
						'type',
						array(
							'constrain' => lang('image_manip_type_opt_constrain'),
							'crop' => lang('image_manip_type_opt_crop'),
						)
					),
					form_input('width'),
					form_input('height')
				)
			)
		));

		if ( ! empty($upload_id))
		{
			// Populate existing image manipulations
			//$grid->setData($data);
		}

		$vars['sections']['upload_image_manipulations'] = array(
			array(
				'title' => 'constrain_or_crop',
				'desc' => 'constrain_or_crop_desc',
				'wide' => TRUE,
				'grid' => TRUE,
				'fields' => array(
					'image_manipulations' => array(
						'type' => 'html',
						'content' => ee()->load->view('_shared/table', $grid->viewData(), TRUE)
					)
				)
			)
		);

		ee()->load->model('member_model');
		$groups = ee()->member_model->get_upload_groups()->result();

		$member_groups = array();
		foreach ($groups as $group)
		{
			$member_groups[$group->group_id] = $group->group_title;
		}

		$no_access = array();
		if ( ! empty($upload_id))
		{
			$no_access = ee()->api->get('UploadDestination')
				->filter('id', $upload_id)
				->first()
				->getNoAccess()
				->pluck('group_id');
		}

		$allowed_groups = array_diff(array_keys($member_groups), $no_access);

		$vars['sections']['upload_privileges'] = array(
			array(
				'title' => 'upload_member_groups',
				'desc' => 'upload_member_groups_desc',
				'fields' => array(
					'avatar_path' => array(
						'type' => 'checkbox',
						'choices' => $member_groups,
						'value' => $allowed_groups
					)
				)
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'name',
				'label' => 'lang:upload_name',
				'rules' => 'required'
			),
			array(
				'field' => 'server_path',
				'label' => 'lang:upload_path',
				'rules' => 'required'
			),
			array(
				'field' => 'url',
				'label' => 'lang:upload_url',
				'rules' => 'callback_not_http'
			),
			array(
				'field' => 'allowed_types',
				'label' => 'lang:upload_allowed_types',
				'rules' => 'required'
			),
			array(
				'field' => 'max_size',
				'label' => 'lang:upload_file_size',
				'rules' => 'integer'
			),
			array(
				'field' => 'max_width',
				'label' => 'lang:upload_image_width',
				'rules' => 'integer'
			),
			array(
				'field' => 'max_height',
				'label' => 'lang:upload_image_height',
				'rules' => 'integer'
			)
		));

		$base_url = cp_url('settings/uploads/'.$upload_id ?: '');

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			// Save settings here

			ee()->functions->redirect($base_url);
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee()->view->set_message('issue', lang('settings_save_error'), lang('settings_save_error_desc'));
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->base_url = $base_url;
		ee()->view->cp_page_title = (empty($upload_id)) ? lang('create_upload_directory') : lang('edit_upload_directory');
		ee()->view->save_btn_text = 'btn_create_directory';
		ee()->view->save_btn_text_working = 'btn_create_directory_working';

		ee()->cp->set_breadcrumb(cp_url('files'), lang('file_manager'));
		ee()->cp->set_breadcrumb(cp_url('settings/uploads'), lang('upload_directories'));

		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

/* End of file Uploads.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Settings/Uploads.php */