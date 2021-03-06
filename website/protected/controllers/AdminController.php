<?php

class AdminController extends Controller {
	var $userActions = array();
	public function filters() {
		return array( 
			'accessControl',
			);
	}

	public function accessRules() {
		return array(
			array('allow',
				'actions'=>array('users', 'addUser'),
				'users'=>array('@'),
				'expression' => 'Yii::app()->user->permissions == 2'
				),
			array('allow',
				'users' => array('@'),
				'expression' => 'Yii::app()->user->isAdmin'
				),
			array('deny',
				'users' => array('*'),
				),
			);
	}

	public function actionCheckUpdate() {
		$result = array();
		Yii::import('ext.Updater.index', 1);
		$d = new driversManager;
		if(!$d->init()) {
			$this->redirect($this->createUrl('admin/updater'));
			Yii::app()->end();
		}
		$versions = $d->getVersions();
		if(!$versions) {
			Yii::app()->user->setFlash('notify', array('type' => 'danger', 'message' => Yii::t('admin', 'Update checking failed. Repo access problem')));
			$this->redirect($this->createUrl('admin/updater'));	
			Yii::app()->end();
		}
		$params = array('version', 'SQLversion');
		foreach($params as $value) {
			$model = Settings::model()->findByAttributes(array('option' => $value));
			if($versions[$value] == $model->value) {
				$result[$value] = 1;
			} else {
				$result[$value] = $versions[$value];
			}
		}
		Yii::app()->user->setFlash('versions', json_encode($result));
		Yii::app()->user->setFlash('notify', array('type' => 'success', 'message' => Yii::t('admin', 'Huge success! update checked')));
		$this->redirect($this->createUrl('admin/updater'));
	}

	public function actionUpdateSQLVersion() {
		$result = array();
		Yii::import('ext.Updater.index', 1);
		$d = new driversManager;
		if(!$d->init()) {
			$this->redirect($this->createUrl('admin/updater'));
			Yii::app()->end();
		}
		$versions = $d->getVersions(1);
		if(!$versions) {
			Yii::app()->user->setFlash('notify', array('type' => 'danger', 'message' => Yii::t('admin', 'Update checking failed. Repo access problem')));
			$this->redirect($this->createUrl('admin/updater'));	
			Yii::app()->end();
		}
		$model = Settings::model()->findByAttributes(array('option' => 'SQLversion'));
		if($versions['SQLversion'] == $model->value) {
			Yii::app()->user->setFlash('notify', array('type' => 'warning', 'message' => Yii::t('admin', 'No new version')));	
			$this->redirect($this->createUrl('admin/updater'));	
			Yii::app()->end();
		}
		$filename = $d->getLast('SQLversion');
		if(updaterHelper::update($filename)) {
			Yii::app()->user->setFlash('notify', array('type' => 'success', 'message' => Yii::t('admin', 'Huge success! updated')));
		} else {
			Yii::app()->user->setFlash('notify', array('type' => 'danger', 'message' => Yii::t('admin', 'Fail, cant execute update file')));
		}
		Yii::app()->user->setFlash('versions', json_encode($result));
		$this->redirect($this->createUrl('admin/updater'));
	}

	public function actionUpdateVersion() {
		$result = array();
		Yii::import('ext.Updater.index', 1);
		$d = new driversManager;
		if(!$d->init()) {
			$this->redirect($this->createUrl('admin/updater'));
			Yii::app()->end();
		}
		$versions = $d->getVersions(1);
		if(!$versions) {
			Yii::app()->user->setFlash('notify', array('type' => 'danger', 'message' => Yii::t('admin', 'Update checking failed. Repo access problem')));
			$this->redirect($this->createUrl('admin/updater'));	
			Yii::app()->end();
		}
		$model = Settings::model()->findByAttributes(array('option' => 'version'));
		if($versions['version'] == $model->value) {
			Yii::app()->user->setFlash('notify', array('type' => 'warning', 'message' => Yii::t('admin', 'No new version')));	
			$this->redirect($this->createUrl('admin/updater'));	
			Yii::app()->end();
		}
		$filename = $d->getLast('version');
		//updaterHelper::update($filename, 'files');
		//*
		if(updaterHelper::update($filename, 'files')) {
			Yii::app()->user->setFlash('notify', array('type' => 'success', 'message' => Yii::t('admin', 'Huge success! updated')));
		} else {
			Yii::app()->user->setFlash('notify', array('type' => 'danger', 'message' => Yii::t('admin', 'Fail, cant execute update file')));
		}
		Yii::app()->user->setFlash('versions', json_encode($result));
		$this->redirect($this->createUrl('admin/updater'));
		//*/
	}

	public function actionUpdater() {
		Yii::import('ext.Updater.index', 1);
		$d = new driversManager;
		$versions = $d->getVersions(1);
		Yii::app()->user->setFlash('versions', json_encode($versions));
		$params = array('version', 'SQLversion');
		foreach($params as $value) {
			$model = Settings::model()->findByAttributes(array('option' => $value));
			if(!$model) {
				$model = new Settings;
				$model->option = $value;
				$model->value = '0.1';
				$model->save();
			}
			$models[] = $model;
		}
		$this->render('updater', array('models' => $models, 'last_check' => updaterHelper::lastCheck()));
	}
	
	public function actionSettings() {
		if(isset($_POST['Settings'])) {
			foreach ($_POST['Settings'] as $k => $model) {
				$models[$k] = Settings::model()->findByPK($_POST['Settings'][$k]['id']);
				$models[$k]->attributes = $_POST['Settings'][$k];
				if($models[$k]->validate() && $models[$k]->save()) {
					Yii::app()->user->setFlash('notify', array('type' => 'success', 'message' => Yii::t('admin', 'Huge success! Settings changed')));
				} else {
					Yii::app()->user->setFlash('notify', array('type' => 'danger', 'message' => Yii::t('admin', 'Fail =\, settings not changed')));
				}
			}
		} else {
			$models = Settings::model()->findAll();
		}
		$this->render('settings', array('models' => $models));
	}

	public function actionServers() {
		$this->render('servers/index', array('servers' => Servers::model()->findAll()));
	}

	public function actionServerAdd() {
		$model = new Servers;
		if(isset($_POST['Servers'])) {
			$model->attributes = $_POST['Servers'];
			if($model->validate() && $model->save()) {
				Yii::app()->user->setFlash('notify', array('type' => 'success', 'message' => Yii::t('admin', 'Server added')));
				$this->redirect(array('servers'));
			}
		}
		$this->render('servers/edit', array('model' => $model));
	}
	
	public function actionServerEdit($id) { // TODO add check owner
		$model = Servers::model()->findByPK($id);
		if(!$model) {
			$this->redirect(array('servers'));
		}
		if(isset($_POST['Servers'])) {
			$model->attributes = $_POST['Servers'];
			if($model->validate() && $model->save()) {
				Yii::app()->user->setFlash('notify', array('type' => 'success', 'message' => Yii::t('admin', 'Server changed')));
				$this->redirect(array('servers'));
			} else {
				Yii::app()->user->setFlash('notify', array('type' => 'danger', 'message' => Yii::t('admin', 'Server not changed')));
				$this->redirect(array('serverEdit', 'id' => $model->id));
			}
		}
		$this->render('servers/edit', array('model' => $model));
	}

	public function actionServerDelete($id) {
		$model = Servers::model()->findByPK($id);
		if(!$model) {
			$this->redirect(array('servers'));
		}
		if($model->delete()) {
			Yii::app()->user->setFlash('notify', array('type' => 'success', 'message' => Yii::t('admin', 'Server deleted')));
		} else {
			Yii::app()->user->setFlash('notify', array('type' => 'danger', 'message' => Yii::t('admin', 'Server not deleted')));
		}
		$this->redirect(array('servers'));
	}

	public function actionStat($type, $id) {
		Yii::import('ext.moment.index', 1);
		$momentManager = new momentManager($id);
		$stat = $momentManager->stat($type);
		if(empty($stat)) {
			$this->render('stat/index', array('title' => Yii::t('admin', 'Statistics not avaiable'), 'stat' => array(), 'type' => $type, 'id' => $id));
			Yii::app()->end();
		}
		switch ($type) {
			case 'disk':
			$stat = json_decode($stat, 1);
			foreach($stat as $k => $s) {
				foreach ($s['disk info'] as $key => $value) {
					$s['disk info'][$key] = $this->convertSize($value);
				}
				$stat[$k] = $s;
			}
			$all = $stat;
			break;      

			case 'rtmp':
			$all = str_replace(array('<html><body>', '</html></body>'), '', $stat);
			break;        

			case 'load':
			$stat = json_decode($stat, 1);
			$all = array();
			$stat = array_reverse($stat['statistics']);
			$stat = array_slice($stat, 0, 100);
			$stat = array_reverse($stat);
			foreach($stat as $key => $value) {
				$all['time'][] = $value['time'];
				foreach($value as $k => $v) {
					if($k == 'time') { continue; }
					$all[$k]['min'][] = (float)$v['min'];
					$all[$k]['max'][] = (float)$v['max'];
					$all[$k]['avg'][] = (float)$v['avg'];
				}
			}
			break;
			case 'source_info':
			print_r($stat);
			return true;
			break;

			default:
			$all = array();
			break;
		}

		$this->render('stat/index', array('title' => Yii::t('admin', 'Statistics(100 recent changes)'), 'stat' => $all, 'type' => $type, 'id' => $id));
	}

	function convertSize($s) {
		if (is_int($s)) {
			$s = sprintf("%u", $s);		
		} if($s >= 1073741824) {
			return sprintf('%1.2f', $s / 1073741824 ). ' GB';
		} elseif($s >= 1048576) {
			return sprintf('%1.2f', $s / 1048576 ) . ' MB';
		} elseif($s >= 1024) {
			return sprintf('%1.2f', $s / 1024 ) . ' KB';
		} else {
			return $s . ' B';
		}
		$this->render('stat', array('title' => 'Statistics(100 recent changes)', 'stat' => $all));
	}

	public function actionCams() {
		$id = Yii::app()->user->getId();
		if(isset($_POST['CamsForm']) && !empty($_POST['CamsForm']) && array_sum($_POST['CamsForm']) != 0) {
			if(isset($_POST['public'])) {
				foreach ($_POST['CamsForm'] as $key => $cam) {
					if($cam) {
						$key = explode('_', $key);
						$cam = Cams::model()->findByPK((int)$key[1]);
						if($cam) {
							$cam->is_public = $cam->is_public ? 0 : 1;
							if($cam->save()) {
								Yii::app()->user->setFlash('notify', array('type' => 'success', 'message' => Yii::t('admin', 'Cams settings successfully changed')));
							}
						}
					}
				}
			}
		}
		$myCams = Cams::model()->findAllByAttributes(array('user_id' => $id));
		$public = Cams::model()->findAllByAttributes(array('is_public' => 1));
		$criteria = new CDbCriteria();
		$criteria->addNotInCondition('id', CHtml::listData($myCams, 'id', 'id'));
		$criteria->addNotInCondition('id', CHtml::listData($public, 'id', 'id'));
		$count = Cams::model()->count($criteria);
		$pages = new CPagination($count);
		$pages->pageSize = 10;
		$pages->applyLimit($criteria);
		$all = Cams::model()->findAll($criteria);
		$this->render(
			'cams/index',
			array(
				'form' => new CamsForm,
				'myCams' => $myCams,
				'publicCams' => $public,
				'allCams' => $all,
				'pages' => $pages
				)
			);
	}

	public function actionUsers() {
		if(isset($_POST['UsersForm']) && !empty($_POST['UsersForm']) && array_sum($_POST['UsersForm']) != 0) {
			foreach ($_POST['UsersForm'] as $key => $user) {
				if($user) {
					$key = explode('_', $key);
					$user = Users::model()->findByPK((int)$key[1]);
					if($user) {
						$this->userAction($_POST, $user);
					}
				}
			}
		}
		$admins = Users::model()->findAllByAttributes(array('status' => 3));
		$operators = Users::model()->findAllByAttributes(array('status' => 2));
		$viewers = Users::model()->findAllByAttributes(array('status' => 1));
		$banned = Users::model()->findAllByAttributes(array('status' => 4));
		$criteria = new CDbCriteria();
		$criteria->addNotInCondition('id', CHtml::listData($admins, 'id', 'id'));
		$criteria->addNotInCondition('id', CHtml::listData($banned, 'id', 'id'));
		$criteria->addNotInCondition('id', CHtml::listData($operators, 'id', 'id'));
		$criteria->addNotInCondition('id', CHtml::listData($viewers, 'id', 'id'));
		$count = Users::model()->count($criteria);
		$pages = new CPagination($count);
		$pages->pageSize = 10;
		$pages->applyLimit($criteria);
		$all = Users::model()->findAll($criteria);
		$this->render(
			Yii::app()->user->permissions == 2 ? 'users/oper' : 'users/index',
			array(
				'form' => new UsersForm,
				'admins' => $admins,
				'operators' => $operators,
				'viewers' => $viewers,
				'banned' => $banned,
				'all' => $all,
				'pages' => $pages
				)
			);
	}

	private function userAction($actions, $user) {
		$this->userActions = array(
			'ban' => array(4, Yii::t('admin', 'banned')),
			'unban' => array(1, Yii::t('admin', 'unbanned')),
			'levelup' => array(3, Yii::t('admin', 'up')),
			'active' => array(1, Yii::t('admin', 'activated')),
			'dismiss' => array(1, Yii::t('admin', 'down')),
			);
		foreach ($this->userActions as $key => $value) {
			if(isset($actions[$key])) {
				if($key == 'levelup' && Yii::app()->user->permissions == 3) {
					$user->status++;
				} elseif($key == 'dismiss' && Yii::app()->user->permissions == 3) {
					$user->status--;
				} else {
					$user->status = $value[0];
				}
				if($user->save()) {
					Yii::app()->user->setFlash('notify', array('type' => 'success', 'message' => Yii::t('admin', 'Users {action}', array('{action}' => $value[1]))));
					return;
				}
			}
		}	
	}

	public function actionLogs($type) {
		if($type == 'system') {
			$logs = Notifications::model()->findAllByAttributes(array('creator_id' => 0), array('order' => 'time DESC'));
		} else {
			$logs = Notifications::model()->findAll(array('condition' => 'creator_id > 0', 'order' => 'time DESC'));
		}
		$this->render('logs/index', array('type' => $type, 'logs' => $logs));
	}

	public function actionAddUser() {
		$model = new UserForm;
		if(isset($_POST['UserForm'])) {
			$model->attributes = $_POST['UserForm'];
			if($model->validate() && $model->register()) {
				Yii::app()->user->setFlash('notify', array('type' => 'success', 'message' => Yii::t('admin', 'User added')));
				$this->redirect(array('users'));
			} else {
				Yii::app()->user->setFlash('notify', array('type' => 'danger', 'message' => Yii::t('admin', 'User not added')));
			}
		}
		$this->render('users/edit', array('model' => $model));
	}

}