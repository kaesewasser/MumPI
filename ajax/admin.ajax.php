<?php
/**
 * Ajax functionality
 * @author Kissaki
 */

require_once dirname(__FILE__).'/ajax.ajax.php';

/**
 * ajax functionality, functions for the admin section
 * @author Kissaki
 */
class Ajax_Admin extends Ajax
{
	public static function getPage()
	{
		TemplateManager::parseTemplate($_GET['page']);
	}

	public static function db_admins_groups_get()
	{
		if (!PermissionManager::getInstance()->serverCanEditAdmins())
			return ;

		$groups = DBManager::getInstance()->getAdminGroups();
		echo json_encode($groups);
	}

	public static function db_adminGroupHeads_get()
	{
		if (!PermissionManager::getInstance()->serverCanEditAdmins())
			return ;

		$groups = DBManager::getInstance()->getAdminGroupHeads();
		echo json_encode($groups);
	}

	public static function db_admingroups_echo()
	{
		if (!PermissionManager::getInstance()->serverCanEditAdmins())
			return ;
?>
		<table>
			<thead><tr><th>ID</th><th>name</th><th>permissions</th><th>servers</th><th>actions</th></tr></thead>
			<tbody>
<?php
				$groups = DBManager::getInstance()->getAdminGroups();
				foreach ($groups AS $group) {
					echo '<tr>
						<td>' . $group['id'] . '</td>
						<td>' . $group['name'] . '</td>
						<td style="font-size:0.6em;">';

					// create permissions string
					$tmp = '';
					foreach ($group['perms'] AS $key=>$val) {
						if ($key != 'groupID' && $val) {
							$tmp .= ', ' . $key;
						}
					}
					// strip leading comma
					if (!empty($tmp)) {
						$tmp = substr($tmp, 2);
					}
					echo $tmp;

					echo '</td>';

					// admin on servers
					echo '<td>';
					$tmp = '';
					foreach ($group['adminOnServers'] AS $srv) {
						$tmp .= $srv.', ';
					}
					echo substr($tmp, 0, strlen($tmp)-2);
					echo '</td>';

					echo '<td>';
					echo 	'<a class="jqlink" onclick="jq_admingroup_perms_edit_display(' . $group['id'] . ')">edit perms</a>, ';
					echo 	'<a class="jqlink" onclick="jq_admingroup_server_assoc_edit_display(' . $group['id'] . ')">edit servers</a>, ';
					echo 	'<a class="jqlink" onclick="jq_admingroup_remove(' . $group['id'] . ')">delete</a>';
					echo '</td>';
					echo '</tr>';
				}
?>
			</tbody>
		</table>
<?php
	}

	public static function db_adminGroup_add()
	{
		if (!PermissionManager::getInstance()->serverCanEditAdmins())
			return ;

		DBManager::getInstance()->addAdminGroup($_POST['name']);
		MessageManager::echoAll();
	}

	public static function db_adminGroup_remove()
	{
		if (!PermissionManager::getInstance()->serverCanEditAdmins())
			return ;

		DBManager::getInstance()->removeAdminGroup(intval($_POST['id']));
		MessageManager::echoAll();
	}

	public static function db_adminGroup_perms_edit_display()
	{
		// TODO server specific perms
		if (!PermissionManager::getInstance()->serverCanEditAdmins())
			return ;

		// exit on missing params
		if (!isset($_POST['groupID']))
			exit();

		// make sure only ints are passed
		$_POST['groupID'] = intval($_POST['groupID']);
		$_POST['serverID'] = isset($_POST['serverID'])?intval($_POST['serverID']):null;
		$group = DBManager::getInstance()->getAdminGroup($_POST['groupID']);

		// output
		echo '<ul class="form_group_permissions">';
		foreach ($group['perms'] AS $key=>$val) {
			if ($key != 'groupID' && $key != 'serverID') {
				echo sprintf('<li><input type="checkbox" name="%s"%s onclick="jq_admingroup_perm_update(%d, \'%s\', %s);"/> %s</li>',
						$key, $val==true?' checked="checked"':'', $_POST['groupID'], $key, "$('input[name=".$key."]').attr('checked')", $key
					);
			}
		}
		echo '</ul>';
	}

	public static function db_adminGroup_perm_update()
	{
		if (!PermissionManager::getInstance()->serverCanEditAdmins()) {
			MessageManager::addError('Insufficient privileges.');
		} else {
			DBManager::getInstance()->updateAdminGroupPermission(intval($_POST['gid']), $_POST['perm'], ($_POST['newval']=='true')?true:false);
		}
		MessageManager::echoAll();
	}

	public static function db_adminGroup_perms_edit()
	{
		if (!PermissionManager::getInstance()->serverCanEditAdmins())
			return ;

		// TODO: [security] perms should only hold the correct keys and boolean vals
		$group = DBManager::getInstance()->updateAdminGroupPermissions(intval($_POST['gid']), $_POST['perms']);
		MessageManager::echoAll();
	}

	public static function db_adminGroups_makeAdminOnServer()
	{
		if (!PermissionManager::getInstance()->serverCanEditAdmins()) {
			return;
		}

		$groupID = intval($_POST['groupID']);
		$serverID = intval($_POST['serverID']);

		DBManager::getInstance()->makeAdminGroupAdminOfServer($groupID, $serverID);
	}
	public static function db_adminGroups_revokeAdminOnServer()
	{
		if (!PermissionManager::getInstance()->serverCanEditAdmins()) {
			return;
		}

		$groupID = intval($_POST['groupID']);
		$serverID = intval($_POST['serverID']);

		DBManager::getInstance()->removeAdminGroupAsAdminOfServer($groupID, $serverID);
	}
	public static function db_adminGroup_servers_edit_display()
	{
		if (!PermissionManager::getInstance()->serverCanEditAdmins())
			return ;

		// exit on missing params
		if (!isset($_POST['groupID']))
			exit();

		// make sure only ints are passed
		$_POST['groupID'] = intval($_POST['groupID']);
		$group = DBManager::getInstance()->getAdminGroup($_POST['groupID']);
		$servers = ServerInterface::getInstance()->getServers();

		// output
		echo '<ul class="form_group_servers">';
		foreach ($servers AS $srv) {
			echo sprintf('<li><input type="checkbox" name="%s"%s onclick="jq_adminGroup_server_update(%d, %d, %s);"/> %s</li>',
					'srv'.$srv->id(), in_array($srv->id(), $group['adminOnServers'])?' checked="checked"':'', $_POST['groupID'], $srv->id(), "$('input[name=srv".$srv->id()."]').attr('checked')", SettingsManager::getInstance()->getServerName($srv->id())
				);
		}
		echo '</ul>';
	}

	public static function db_admins_echo()
	{
		if (!PermissionManager::getInstance()->serverCanEditAdmins())
			return ;

		echo '<table class="list_admins"><thead><tr class="head"><th>Username</th><th>global Admin</th><th>Groups</th><th>Actions</th></tr></thead>';
		echo '<tbody>';
		$admins = DBManager::getInstance()->getAdmins();
		foreach ($admins AS $admin) {
			$groups = DBManager::getInstance()->getAdminGroupsByAdminID($admin['id']);

			echo '<tr id="admin_list_item_'.$admin['id'].'" class="list_admins_item">';
			echo 	'<td>'.$admin['name'].'</td>';
			echo 	'<td>' . ($admin['isGlobalAdmin'] ? 'yes' : 'no') . '</td>';
			echo 	'<td>';

			echo 		'<ul class="list_groups">';
			foreach ($groups AS $group) {
				echo 		'<li>' . $group['name'] . '</li>';
			}
			echo 		'</ul>';

			echo 	'</td>';
			echo 	'<td>';
			echo 		'<ul>';
			// TODO: I18N
			if (empty($groups))
				echo 			'<li><a title="add" class="jqlink" onclick="jq_admin_addToGroup_display(' . $admin['id'] . ');">addToGroup</a></li>';
			else
				echo 			'<li><a title="add" class="jqlink" onclick="jq_admin_removeFromGroups(' . $admin['id'] . ');">removeFromGroups</a></li>';
			// TODO: I18N
			// if this is the account you're currently logged in as ask explicitly
			if (SessionManager::getInstance()->getAdminID() == $admin['id']) {
				echo '<li><a class="info" title="You can not remove your own account. Instead, use another super-admin account to remove it." style="font-style:strikethrough;"><s>delete</s></a></li>';
			} else {
				echo '<li><a class="jqlink" onclick="jq_admin_remove('.$admin['id'].');">delete</a></li>';
			}
			echo 		'</ul>';
			echo 	'</td>';
			echo '</tr>';
		}
		echo 	'</tbody>';
		echo '</table>';
	}

	public static function db_admin_update_name()
	{
		if (!PermissionManager::getInstance()->serverCanEditAdmins())
			return ;

		DBManager::getInstance()->updateAdminName($_POST['name'], $_POST['pw']);
	}

	public static function db_admin_add()
	{
		if (!PermissionManager::getInstance()->serverCanEditAdmins())
			return ;
		DBManager::getInstance()->addAdmin(strip_tags($_POST['name']), strip_tags($_POST['pw']), strip_tags($_POST['isGlobalAdmin']));
		MessageManager::echoAllErrors();
	}

	public static function db_admin_remove()
	{
		if (!PermissionManager::getInstance()->serverCanEditAdmins())
			return ;

		DBManager::getInstance()->removeAdminLogin($_POST['id']);
		MessageManager::echoAllErrors();
	}

	/**
	 * requires admin id 'aid' and group id 'gid' as _POST
	 */
	public static function db_admin_addToGroup()
	{
		if (!PermissionManager::getInstance()->serverCanEditAdmins())
			return ;

		DBManager::getInstance()->addAdminToGroup($_POST['aid'], $_POST['gid']);
		MessageManager::echoAllErrors();
	}

	/**
	 * requires admin id 'aid' as _POST
	 */
	public static function db_admin_removeFromGroups()
	{
		if (!PermissionManager::getInstance()->serverCanEditAdmins())
			return ;

		DBManager::getInstance()->removeAdminFromGroup($_POST['aid']);
		MessageManager::echoAllErrors();
	}

	/**
	 * requires group id 'aid' as _POST
	 */
	public static function db_admin_addToGroup_display()
	{
		if (!PermissionManager::getInstance()->serverCanEditAdmins())
			return ;

		$aid = intval($_POST['aid']);

		$admin = DBManager::getInstance()->getAdmin($aid);
		$groups = DBManager::getInstance()->getAdminGroups();

		echo 'Add ' . $admin['name'] . ' to group:<br/>';
		echo '<ul>';
		foreach ($groups AS $group) {
			echo '<li><a class="jqlink" onclick="jq_admin_addToGroup(' . $aid . ', ' . $group['id'] . ');">' . $group['name'] . '</a></li>';
		}
		echo '</ul>';
	}

	public static function meta_showDefaultConfig()
	{
		$config = ServerInterface::getInstance()->getDefaultConfig();
		echo '<table>';
		foreach ($config AS $key=>$value) {
			echo '<tr><td>'.$key.':</td><td>'.$value.'</td></tr>';
		}
		echo '</table>';
		MessageManager::echoAllErrors();
	}

	public static function server_create()
	{
		if (!PermissionManager::getInstance()->isGlobalAdmin())
			return ;

		echo ServerInterface::getInstance()->createServer();
	}

	public static function server_delete()
	{
		$serverId = intval($_POST['sid']);
		if (!PermissionManager::getInstance()->isGlobalAdmin($serverId))
			return ;

		ServerInterface::getInstance()->deleteServer($serverId);
		SettingsManager::getInstance()->removeServerInformation($serverId);
	}

	public static function server_start()
	{
		$_POST['sid'] = intval($_POST['sid']);
		if (!PermissionManager::getInstance()->serverCanStartStop($_POST['sid']))
			return ;

		ServerInterface::getInstance()->startServer($_POST['sid']);
	}

	public static function server_stop()
	{
		$_POST['sid'] = intval($_POST['sid']);
		if (!PermissionManager::getInstance()->serverCanStartStop($_POST['sid']))
			return ;

		ServerInterface::getInstance()->stopServer($_POST['sid']);
	}

	public static function server_setSuperuserPassword()
	{
		$_POST['sid'] = intval($_POST['sid']);
		if (!PermissionManager::getInstance()->serverCanGenSuUsPW($_POST['sid']))
			return ;

		ServerInterface::getInstance()->setServerSuperuserPassword($_POST['sid'], $_POST['pw']);
	}

	public static function server_getRegistrations()
	{
		$serverId = intval($_POST['sid']);
		if (!PermissionManager::getInstance()->serverCanViewRegistrations($serverId)) {
			echo tr('permission_denied');
			MessageManager::echoAllMessages();
			exit();
		}

		try {
			$server = MurmurServer::fromIceObject(ServerInterface::getInstance()->getServer($serverId));
			$users = $server->getRegisteredUsers();
?>
			<h2>Registrations</h2>
			<table>
				<thead>
					<tr>
						<th>User ID</th>
						<th>Username</th>
						<th>email</th>
						<th>comment</th>
						<th>cert hash</th>
						<th>avatar</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
<?php
					foreach ($users AS $userId=>$userName) {
						//FIXME Ice version check, enum-index available? otherwise, one has to edit his slice file – actually, this fixme should be a general check, in install or general warning-disableable
						$user = ServerInterface::getInstance()->getServerRegistration($serverId, $userId);
?>
					<tr>
						<td>
							<?php echo $userId; ?>
						</td>
						<td id="user_name_<?php echo $userId; ?>" class="jq_editable"><?php echo $userName; ?></td>
						<td id="user_email_<?php echo $userId; ?>" class="jq_editable"><?php echo $user->getEmail(); ?></td>
						<td id="userComment<?php echo $user->getUserId(); ?>" class="comment userComment">
							<?php
								$commentClean = htmlspecialchars($user->getComment());
								if (!empty($commentClean)) {
									?>
										<a title="Toggle display of full comment. HTML is escaped to ensure your safety viewing it." href="javascript:toggleUserComment(<?php echo $user->getUserId(); ?>);" style="float:left; margin-right:4px;">
											○
										</a>
										<div class="teaser"><?php echo substr($commentClean, 0, 10) . ((strlen($commentClean)>10)?'…':''); ?></div>
										<div class="complete" style="display:none;"><?php echo $commentClean; ?></div>
										<script type="text/javascript">/*<![CDATA[*/
											// toggle display of user comment teaser <-> full
											function toggleUserComment(userId)
											{
												jQuery('#userComment' + userId + ' .teaser').css('display', (jQuery('#userComment' + userId + ' .teaser').css('display')=='block'?'none':'block'));
												jQuery('#userComment' + userId + ' .complete').css('display', (jQuery('#userComment' + userId + ' .complete').css('display')=='block'?'none':'block'));
											}
											/*]]>*/
										</script>
									<?php
								}
							?>
						</td>
						<td id="user_hash_<?php echo $userId; ?>" class="userHash jq_editable"><?php echo $user->getHash(); ?></td>
						<td>
							<?php
								$userAvatarByteSequence = $server->getTexture($user->getUserId());
								$hasAvatar = count($userAvatarByteSequence) > 0;
								if ($hasAvatar) {
									$texBytes = '';
									foreach ($userAvatarByteSequence as $val) {
										$texBytes .= chr($val);
									}
									$texB64 = base64_encode($texBytes);
								}
							?>
							<div class="userAva">
								<?php
									if ($hasAvatar) {
										?>
											<div class="userAvaLinks">
												<a class="userAvaToggle jqlink" style="font-style:italic;">
													show
												</a><br/>
												<a class="jqlink" onclick="if(confirm('Are you sure you want to remove this users avatar?')){jq_user_updateAvatar(<?php echo $serverId; ?>, <?php echo $userId; ?>, null);}">
													remove
												</a>
											</div>
											<div class="userAvaImage" style="display:none;">
															<img src="data:image/*;base64,<?php echo $texB64; ?>" alt="" />
											</div>
										<?php
									} else {
										?>
											none
										<?php
									}
								?>
							</div>
						</td>
						<td>
<?php
							if (PermissionManager::getInstance()->serverCanEditRegistrations($serverId)) {
								echo '<ul>';
								echo '	<li><a class="jqlink" onclick="if(confirm(\'Do you really want to remove the user ' . str_replace('"', '', $userName) . '?\')){jq_server_registration_remove('.$userId.');}">remove</a></li>';
								echo '	<li><a title="generate a new password for the user" class="jqlink" onclick="if(confirm(\'Are you sure you want to generate and set a new password for this account?\')){jq_server_user_genNewPw('.$user->getServerId().', '.$user->getUserId().'); return false;}">genNewPw</a></li>';
								echo '</ul>';
							}
?>
						</td>
					</tr>
<?php
				} // /foreach users
?>
				</tbody>
			</table>
			<script type="text/javascript">
				/*<![CDATA[*/
					jQuery('.userAvaToggle').toggle(
							function (eventObj) {
							  jQuery(this).parent().parent().find('.userAvaImage').css('display', 'block');
							  jQuery(this).html('hide');
							},
							function (eventObj) {
							  jQuery(this).parent().parent().find('.userAvaImage').css('display', 'none');
							  jQuery(this).html('show');
							}
						);

					jQuery('.mpi_userComment_form textarea')
						.resizable()
						.css('padding', '0');

					function displayUserCommentChangeDialog(userId) {
						jQuery('#userComment' + userId)
							.append('<div id="userComment' + userId + 'ChangeForm"><textarea rows="8" cols="80">' + jQuery('#userComment' + userId + ' .complete').html() + '</textarea></div>');

						jQuery('#userComment' + userId + 'ChangeForm')
							.dialog(
									{
										title: 'Update User Comment',
										width: 'auto',
										height: 'auto',
										modal: true,
										buttons: {
											'Cancel': function () {
													jQuery(this).dialog('close');
												},
									  	'Update': function () {
											  	var newComment = jQuery(this).find('textarea').val();
											  	jq_user_updateComment(<?php echo $serverId; ?>, userId, newComment);
										  		jQuery(this).dialog('close');
									  		}
											}
									}
								);
					}
					jQuery('.userComment').each(
							function (index, el)
							{
								jQuery(el).dblclick(
										function()
										{
										  userId = jQuery(this).attr('id').substr(11);
											displayUserCommentChangeDialog(userId);
										}
									);
							}
						);
				/*]]>*/
			</script>
<?php
		} catch(Murmur_ServerBootedException $exc) {
			echo '<div class="error">Server is not running</div>';
		}
	}

	public static function show_onlineUsers()
	{
		$_POST['sid'] = intval($_POST['sid']);
		if (!PermissionManager::getInstance()->isAdminOfServer($_POST['sid'])) {
			echo tr('permission_denied');
			MessageManager::echoAllMessages();
			exit();
		}
		$canModerate = PermissionManager::getInstance()->serverCanModerate($_POST['sid']);

		$users = array();
		try {
			$users = ServerInterface::getInstance()->getServerUsersConnected($_POST['sid']);
?>
			<h2>Online Users</h2>
			<table id="mpi_table_onlineusers">
				<thead>
					<tr>
						<th>Sess ID</th>
						<th>Reg ID</th>
						<th>Username</th>
						<th></th>

						<th>muted?</th>
						<th>deaf?</th>
						<th>suppressed</th>
						<th>selfMuted</th>
						<th>selfDeafened</th>

						<th>time online</th>
						<th>idle</th>
						<th>B/s</th>
						<th>client</th>
						<th>comment</th>
						<th>address</th>
						<th>TCPonly</th>

						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
<?php				foreach ($users AS $user) {	?>
					<tr>
						<td><?php echo $user->getSessionId(); ?></td>
						<td><?php if ($user->getRegistrationId() !== -1) echo $user->getRegistrationId(); ?></td>
						<td id="user_name_<?php echo $user->sessionId; ?>" class="jq_editable"><?php echo $user->name; ?></td>
						<td><?php echo $user->get ?></td>
						<td><input id="user_mute_<?php echo $user->getSessionId(); ?>" class="jq_toggleable" type="checkbox" <?php if ($user->getIsMuted()) echo 'checked=""'; if(!$canModerate) echo 'disabled=""'; ?>/></td>
						<td><input id="user_deaf_<?php echo $user->getSessionId(); ?>" class="jq_toggleable" type="checkbox" <?php if ($user->getIsDeafened()) echo 'checked=""'; if(!$canModerate) echo 'disabled=""'; ?>/></td>
						<td><input id="user_suppr_<?php echo $user->getSessionId(); ?>" class="jq_toggleable" type="checkbox" <?php if ($user->getIsSuppressed()) echo 'checked=""'; echo 'disabled=""'; ?>/></td>
						<td><input id="user_selfm_<?php echo $user->getSessionId(); ?>" class="jq_toggleable" type="checkbox" <?php if ($user->getIsSelfMuted()) echo 'checked=""'; echo 'disabled=""'; ?>/></td>
						<td><input id="user_selfd_<?php echo $user->getSessionId(); ?>" class="jq_toggleable" type="checkbox" <?php if ($user->getIsSelfDeafened()) echo 'checked=""'; echo 'disabled=""'; ?>/></td>

						<td id="user_email_<?php echo $user->getSessionId(); ?>" class="jq_editable">
							<?php $on = $user->getOnlineSeconds(); if ($on > 59) { echo sprintf('%.0f', $on/60).'m'; } else { echo $on.'s'; } ?>
						</td>
						<td>
							<?php $idle = $user->getIdleSeconds(); if ($idle > 59) { echo sprintf('%.0f', $idle/60).'m'; } else { echo $idle.'s'; } ?>
						</td>
						<td><?php echo $user->getBytesPerSecond(); ?></td>
						<td><?php echo $user->clientVersion() . ($user->clientVersion()!=$user->clientRelease())?$user->clientRelease():'' . $user->clientOs() . $user->clientOsVersion(); ?></td>
						<td id="userComment<?php echo $user->getSessionId(); ?>" class="comment userComment">
							<?php $commentClean = htmlspecialchars($user->getComment()); ?>
							<?php
								if (!empty($commentClean)) {
									if (strlen($commentClean) > 10) {
										?>
											<a title="Toggle display of full comment. HTML is escaped to ensure you can safely view it." href="javascript:toggleUserComment(<?php echo $user->getSessionId(); ?>);" style="float:left; margin-right:4px;">○</a>
										<?php
											}
										?>
										<div class="teaser">
											“<?php echo ((strlen($commentClean) > 10) ? substr($commentClean, 0, 10) . '…' : $commentClean); ?>“
										</div>
										<div class="complete" style="display:none;">
											<?php echo $commentClean; ?>
										</div>
										<script type="text/javascript">/*<![CDATA[*/
											// toggle display of user comment teaser <-> full
											function toggleUserComment(userSessionId)
											{
												jQuery('#userComment' + userSessionId + ' .teaser').css('display', (jQuery('#userComment' + userSessionId + ' .teaser').css('display')=='block'?'none':'block'));
												jQuery('#userComment' + userSessionId + ' .complete').css('display', (jQuery('#userComment' + userSessionId + ' .complete').css('display')=='block'?'none':'block'));
											}
											/*]]>*/
										</script>
									<?php
								}
							?>
						</td>
						<td class="userAddress">
							<?php echo $user->getAddress()->__toString(); ?> <sup>(<a href="http://[<?php echo $user->getAddress(); ?>]">http</a>, <a href="http://www.db.ripe.net/whois?searchtext=<?php echo $user->getAddress(); ?>">lookup</a>)</sup>
							<?php if ($user->getAddress()->isIPv4()) { echo '<div>' . $user->getAddress()->toStringAsIPv4() . '</div>'; } ?>
						</td>
						<td><?php echo $user->getIsTcpOnly()?'true':'false'; ?></td>

						<td>
<?php
						if (PermissionManager::getInstance()->serverCanKick($_POST['sid']))
							echo '<a class="jqlink" onclick="jq_server_user_kick(' . $user->getSessionId() . ')">kick</a>';
?>
						</td>
					</tr>
<?php				}	?>
				</tbody>
			</table>
<?php
			if ($canModerate) {
?>
				<script type="text/javascript">/*<![CDATA[*/
					$('.jq_toggleable').click(
							function(event)
							{
								var id = $(this).attr('id');
								var sub = id.substring(0, id.lastIndexOf('_'));
								var id = id.substring(id.lastIndexOf('_')+1, id.length);
								switch (sub) {
									case 'user_mute':
										if ($(this).attr('checked')) {
											jq_server_user_mute(id);
										} else {
											jq_server_user_unmute(id);
										}

										break;
									case 'user_deaf':
										if ($(this).attr('checked')) {
											jq_server_user_deaf(id);
										} else {
											jq_server_user_undeaf(id);
										}
										break;
								}
							}
						);
					/*]]>*/
				</script>
<?php
			} // permission check: moderate
		} catch(Murmur_ServerBootedException $exc) {
			echo '<div class="error">Server is not running</div>';
		}
	} // show_onlineUsers()

	public static function server_regstration_remove()
	{
		$_POST['sid'] = intval($_POST['sid']);
		if (!PermissionManager::getInstance()->serverCanEditRegistrations($_POST['sid']))
			return ;

		ServerInterface::getInstance()->removeRegistration($_POST['sid'], $_POST['uid']);
	}
	public static function server_regstration_genpw()
	{
		$serverId = intval($_POST['serverId']);
		$userId = $_POST['userId'];
		$newPw = $_POST['newPw'];
		if (!PermissionManager::getInstance()->serverCanEditRegistrations($_POST['serverId']))
			return ;
		$reg = ServerInterface::getInstance()->getServerRegistration($serverId, $userId);
		$reg->setPassword($newPw);
		ServerInterface::getInstance()->saveRegistration($reg);
	}

	public static function server_user_mute()
	{
		$_POST['sid'] = intval($_POST['sid']);
		if (!PermissionManager::getInstance()->serverCanModerate($_POST['sid']))
			return ;

		ServerInterface::getInstance()->muteUser($_POST['sid'], $_POST['sessid']);
	}

	public static function server_user_unmute()
	{
		$_POST['sid'] = intval($_POST['sid']);
		if (!PermissionManager::getInstance()->serverCanModerate($_POST['sid']))
			return ;

		ServerInterface::getInstance()->unmuteUser($_POST['sid'], $_POST['sessid']);
	}

	public static function server_user_deaf()
	{
		$_POST['sid'] = intval($_POST['sid']);
		if (!PermissionManager::getInstance()->serverCanModerate($_POST['sid']))
			return ;

		ServerInterface::getInstance()->deafUser($_POST['sid'], $_POST['sessid']);
	}

	public static function server_user_undeaf()
	{
		$_POST['sid'] = intval($_POST['sid']);
		if (!PermissionManager::getInstance()->serverCanModerate($_POST['sid']))
			return ;

		ServerInterface::getInstance()->undeafUser($_POST['sid'], $_POST['sessid']);
	}

	public static function server_user_kick()
	{
		$_POST['sid'] = intval($_POST['sid']);
		if (PermissionManager::getInstance()->serverCanKick($_POST['sid']))
			ServerInterface::getInstance()->kickUser($_POST['sid'], $_POST['sessid']);
	}

	public static function show_server_bans()
	{
		$serverId = intval($_POST['sid']);
		if (!PermissionManager::getInstance()->isAdminOfServer($serverId)) {
			echo tr('permission_denied');
			MessageManager::echoAllMessages();
			exit();
		}
		$bans = array();
		try {
			$bans = ServerInterface::getInstance()->getServerBans($serverId);
			echo '<h2>Bans</h2>';
			if (PermissionManager::getInstance()->serverCanBan($serverId))
				echo '<p><a class="jqlink" onclick="jq_server_ban_show(' . $serverId . ')">add</a></p>';
			if (count($bans)==0) {
				echo 'no bans on this virtual server';
			} else {
?>
				<table>
					<thead>
						<tr>
							<th>address</th>
							<th>bits</th>
							<th>actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($bans as $ban) { ?>
							<tr>
								<td><?php echo HelperFunctions::int2ip($ban->address); ?></td>
								<td><?php echo $ban->bits; ?></td>
								<td>
									<?php
										if (PermissionManager::getInstance()->serverCanBan($serverId))
											echo "<a class=\"jqlink\" onclick=\"jq_server_unban($serverId, $ban->address, $ban->bits)\">remove</a>";
									?>
							</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
				<br/>
				<p>
					<?php echo tr('info_ip_bits'); ?>
				</p>
<?php
			}
		} catch(Murmur_ServerBootedException $exc) {
			//TODO i18n
			echo 'Server is not running.';
		}
	}
	public static function server_ban_show()
	{
		$serverId = intval($_POST['serverId']);
		echo '<div class="ban_form">';
		echo '<table><thead><tr><th>IP</th><th>bits</th></tr></thead><tbody><tr><td><input type="text" name="ip" value=""/></td><td><input type="text" name="bits" value="32"/></td></tr></tbody></table>';
		echo '<a class="jqlink" onclick="' . "jq_server_ban($serverId, $('.ban_form input[name=ip]').val(), $('.ban_form input[name=bits]').val())" . '">add</a><br/>';
		echo '<br/>';
		echo tr('info_ip_bits');
		echo '</div>';
	}
	public static function server_ban()
	{
		$serverId = intval($_POST['serverId']);
		$ip = strip_tags($_POST['ipmask']);
		$bits = intval($_POST['bits']);
		if (strpos($ip, '.') === false) {
			$ip = intval($ip);
		} else {
			$ip = HelperFunctions::ip2int($ip);
		}
		ServerInterface::getInstance()->ban($serverId, $ip, $bits);
	}
	public static function server_unban()
	{
		$serverId=intval($_POST['serverId']);
		$ipmask=intval($_POST['ipmask']);
		$bits=intval($_POST['bits']);
		if (PermissionManager::getInstance()->serverCanBan($serverId)) {
			ServerInterface::getInstance()->unban($serverId, $ipmask, $bits);
		}
	}

	public static function show_tree()
	{
		$_POST['sid'] = intval($_POST['sid']);
		if (!PermissionManager::getInstance()->isAdminOfServer($_POST['sid'])) {
			echo tr('permission_denied');
			MessageManager::echoAllMessages();
			exit();
		}

		try {
			$tree = ServerInterface::getInstance()->getServer($_POST['sid'])->getTree();
			HelperFunctions::showChannelTree($tree);
		} catch(Murmur_ServerBootedException $exc) {
			//TODO i18n
			echo 'Server is not running.';
		}
	}

	public static function show_acl()
	{
		//TODO: IMPLEMENT show_acl()
	}

	public static function server_config_get()
	{
		$_POST['sid'] = intval($_POST['sid']);
		ServerInterface::getInstance()->getServerConfig($_POST['sid']);
	}

	public static function server_config_show()
	{
		if(!isset($_POST['sid'])) return;
		$_POST['sid'] = intval($_POST['sid']);
		$conf = ServerInterface::getInstance()->getServerConfig($_POST['sid']);
		//TODO i18n
?>
		<h1>Server Config</h1>
		<p>For documentation, see your murmur.ini file (or <a href="http://mumble.git.sourceforge.net/git/gitweb.cgi?p=mumble/mumble;a=blob;f=scripts/murmur.ini;hb=HEAD" rel="external">this</a> one in the repository, which may be newer than yours though)</p>
		<br/>
		<br/>
		<?php $canEdit = PermissionManager::getInstance()->serverCanEditConf($_POST['sid']);
			if ($canEdit) { ?>
			<p style="font-size:x-small;">(Double-click entries to edit them)</p>
		<?php } ?>
		<table><tbody>
			<tr class="table_headline"><td colspan="2">General</td></tr>
			<tr><td>Password</td>		<td class="jq_editable" id="jq_editable_server_conf_password"><?php echo $conf['password']; unset($conf['password']); ?></td></tr>
			<tr><td>Users</td>			<td class="jq_editable" id="jq_editable_server_conf_users"><?php echo $conf['users'];    unset($conf['users']); ?></td></tr>
			<tr><td>Timeout</td>		<td class="jq_editable" id="jq_editable_server_conf_timeout"><?php echo $conf['timeout'];  unset($conf['timeout']); ?></td></tr>
			<tr><td>Host</td>			<td class="jq_editable" id="jq_editable_server_conf_host"><?php echo $conf['host'];     unset($conf['host']); ?></td></tr>
			<tr><td>Port</td>			<td class="jq_editable" id="jq_editable_server_conf_port"><?php echo $conf['port'];     unset($conf['port']); ?></td></tr>
			<tr>
				<td>Default Channel</td>
				<td id="jq_editable_server_conf_defaultchannel">
					<?php
						$defaultChannelId = $conf['defaultchannel'];
						$server = MurmurServer::fromIceObject(ServerInterface::getInstance()->getServer($_POST['sid']));
						$defaultChannel = $server->getChannel($defaultChannelId);
						echo $defaultChannel->getName();

						// change default chan functionality
						$chanTree = $server->getTree();
						function treePrint(MurmurTree $tree, $first=true)
						{
							$subs = $tree->getSubChannels();
							if ($first) {
								echo '<div id="jq_editable_server_conf_defaultchannel_form">';
								//TODO i18n
								echo '<p>Select the channel unregistered and new users are to join when joining the server.</p>';
							}
							?>
								<ul>
									<li class="form_clickable_submit jslink" id="channel_<?php echo $tree->getRootChannel()->getId(); ?>"><?php echo $tree->getRootChannel()->getName(); ?></li>
									<?php
										if (!empty($subs)) {
											foreach ($subs as $subTree) {
												treePrint($subTree, false);
											}
										}
									?>
								</ul>
							<?php
							if ($first) {
								echo '</div>';
							}
						}
						treePrint($chanTree);
					?>
				</td>
			</tr>
			<tr><td>welcometext</td>	<td class="jq_editable" id="jq_editable_server_conf_welcometext"><?php echo $conf['welcometext']; unset($conf['welcometext']); ?></td></tr>

			<tr class="table_headline">	<td colspan="2"></td></tr>
			<tr><td>bandwidth</td>		<td class="jq_editable" id="jq_editable_server_conf_bandwidth"><?php echo $conf['bandwidth']; unset($conf['bandwidth']); ?></td></tr>
			<tr><td>channelname</td>	<td class="jq_editable" id="jq_editable_server_conf_channelname"><?php echo $conf['channelname']; unset($conf['channelname']); ?></td></tr>
			<tr><td>username</td>		<td class="jq_editable" id="jq_editable_server_conf_playername"><?php echo $conf['username']; unset($conf['username']); ?></td></tr>
			<tr><td>textmessagelength</td>		<td class="jq_editable" id="jq_editable_server_conf_playername"><?php echo $conf['textmessagelength']; unset($conf['textmessagelength']); ?></td></tr>
			<tr><td>obfuscate</td>		<td class="jq_editable" id="jq_editable_server_conf_obfuscate"><?php echo $conf['obfuscate']; unset($conf['obfuscate']); ?></td></tr>

			<tr class="table_headline">	 <td colspan="2">Server Registration</td></tr>
			<tr><td>registerhostname</td><td class="jq_editable" id="jq_editable_server_conf_registerhostname"><?php echo $conf['registerhostname']; unset($conf['registerhostname']); ?></td></tr>
			<tr><td>registername</td>	 <td class="jq_editable" id="jq_editable_server_conf_registername"><?php echo $conf['registername']; unset($conf['registername']); ?></td></tr>
			<tr><td>registerpassword</td><td class="jq_editable" id="jq_editable_server_conf_registerpassword"><?php echo $conf['registerpassword']; unset($conf['registerpassword']); ?></td></tr>
			<tr><td>registerurl</td>	 <td class="jq_editable" id="jq_editable_server_conf_registerurl"><?php echo $conf['registerurl']; unset($conf['registerurl']); ?></td></tr>

<?php
		foreach ($conf AS $key=>$val) {
?>
			<tr>
				<td><?php echo $key; ?></td>
				<td class="jq_editable" id="jq_editable_server_conf_<?php echo $key; ?>"><?php echo $val; ?></td>
			</tr>
<?php
		}
?>
		</tbody></table>
<?php
		if ($canEdit) {
?>
			<script type="text/javascript">/*<![CDATA[*/
				var currentServerId = <?php echo $_POST['sid']; ?>;
				function jq_editable_server_conf_onSubmit(obj, content)
				{
					var id = obj.attr('id');
					var subId = id.substring(id.lastIndexOf('_')+1, id.length);
					$.post('./?ajax=server_config_update',
						{ 'sid': currentServerId, 'key': subId, 'value': content.current },
						function(data) {
							jq_server_config_show(currentServerId);
						}
					);
				}
				function jq_editable_server_conf_text2textarea(key)
				{
					$('#jq_editable_server_conf_'+key).editable('destroy').editable({
						'type': 'textarea',
						'submit': 'save',
						'cancel':'cancel',
						'editBy': 'dblclick',
						'onSubmit': function(content){ jq_editable_server_conf_onSubmit($(this), content); }
					});
				}
				$('.jq_editable')
					.editable({
						'type': 'text',
						'submit': 'save',
						'cancel':'cancel',
						'editBy': 'dblclick',
						'onSubmit': function(content){ jq_editable_server_conf_onSubmit($(this), content); }
					});
				jq_editable_server_conf_text2textarea('welcometext');
				jq_editable_server_conf_text2textarea('certificate');
				jq_editable_server_conf_text2textarea('key');

				// default channel editable:
		jQuery('#jq_editable_server_conf_defaultchannel_form').ready(function(){
			jQuery("#jq_editable_server_conf_defaultchannel_form").dialog({
				title: 'Select default channel',
				autoOpen: false,
				height: 'auto',
				width: 'auto',
				modal: true,
				buttons: {
					Cancel: function() {
						$(this).dialog('close');
					}
				}
			});
			jQuery('#jq_editable_server_conf_defaultchannel_form .form_clickable_submit').click(function(event){
					var id = jQuery(this).attr('id');
					var channelId = id.substr(id.indexOf('_')+1);
					$.post(
						'./?ajax=server_config_update',
						{ 'sid': currentServerId, 'key': 'defaultchannel', 'value': channelId },
						function(data) {
							jq_server_config_show(currentServerId);
							jQuery('#jq_editable_server_conf_defaultchannel_form').dialog('close').remove();
						}
					);
				});
			jQuery('#jq_editable_server_conf_defaultchannel')
				.dblclick(function(){
					jQuery('#jq_editable_server_conf_defaultchannel_form').dialog('open');
				});
		});
				/*]]>*/
			</script>
<?php
		}
	}

	public static function server_config_update()
	{
		$_POST['sid'] = intval($_POST['sid']);
		if (PermissionManager::getInstance()->serverCanEditConf($_POST['sid'])) {
			if (isset($_POST['sid']) && isset($_POST['key']) && isset($_POST['value']))
			{
				ServerInterface::getInstance()->setServerConfigEntry($_POST['sid'], $_POST['key'], $_POST['value']);
			}
		}
	}

	public static function server_user_updateUsername()
	{
		$_POST['sid'] = intval($_POST['sid']);
		$_POST['uid'] = intval($_POST['uid']);
		if (PermissionManager::getInstance()->serverCanEditRegistrations($_POST['sid'])) {
			ServerInterface::getInstance()->updateUserName($_POST['sid'], $_POST['uid'], $_POST['newValue']);
		}
	}

	public static function server_user_updateEmail()
	{
		$_POST['sid'] = intval($_POST['sid']);
		$_POST['uid'] = intval($_POST['uid']);
		if (PermissionManager::getInstance()->serverCanEditRegistrations($_POST['sid'])) {
			ServerInterface::getInstance()->updateUserEmail($_POST['sid'], $_POST['uid'], $_POST['newValue']);
		}
	}

	public static function server_user_updateComment()
	{
		$serverId = intval($_POST['sid']);
		$userId = intval($_POST['uid']);
		$newValue = $_POST['newValue'];
		if (PermissionManager::getInstance()->serverCanEditRegistrations($serverId)) {
			ServerInterface::getInstance()->updateUserComment($serverId, $userId, $newValue);
		}
	}

	public static function server_user_updateHash()
	{
		$_POST['sid'] = intval($_POST['sid']);
		$_POST['uid'] = intval($_POST['uid']);
		if (PermissionManager::getInstance()->serverCanEditRegistrations($_POST['sid'])) {
			ServerInterface::getInstance()->updateUserHash($_POST['sid'], $_POST['uid'], $_POST['newValue']);
		}
	}

	public static function server_user_updateAvatar()
	{
		$serverId = intval($_POST['sid']);
		$userId = intval($_POST['uid']);
		$newValue = $_POST['newValue']=='null'?array():$_POST['newValue'];
		if (PermissionManager::getInstance()->serverCanEditRegistrations($serverId)) {
			ServerInterface::getInstance()->updateUserTexture($serverId, $userId, $newValue);
		}
	}

	public static function meta_server_information_edit()
	{
		$_POST['serverid'] = intval($_POST['serverid']);
		if (!PermissionManager::getInstance()->serverCanEditConf($_POST['serverid']))
			return ;

		$server = SettingsManager::getInstance()->getServerInformation($_POST['serverid']);

		echo '<div>';
		if ($server === null) {
			echo 'new:<br/>';
			$server['name']              = '';
			$server['allowlogin']        = true;
			$server['allowregistration'] = true;
			$server['forcemail']         = true;
			$server['authbymail']        = false;
		}
		echo	'<table>';
		echo		'<tr><td>name</td>'
						.'<td><input type="text" id="meta_server_information_name" name="meta_server_information_name" value="'
						.$server['name'].'" /></td></tr>';
		echo		'<tr><td>Allow Login</td>'
						.'<td><input type="checkbox" id="meta_server_information_allowlogin" name="meta_server_information_allowlogin"'
						.($server['allowlogin'] ? ' checked="checked"' : '').'" /></tr>';
		echo		'<tr><td>Allow Registration</td>'
						.'<td><input type="checkbox" id="meta_server_information_allowregistration" name="meta_server_information_allowregistration"'
						.($server['allowregistration'] ? ' checked="checked"' : '').'" /></tr>';
		echo		'<tr><td>Force eMail</td>'
						.'<td><input type="checkbox" id="meta_server_information_forcemail" name="meta_server_information_forcemail"'
						.($server['forcemail'] ? ' checked="checked"' : '').'" /></tr>';
		echo		'<tr><td>Auth by Mail</td>'
						.'<td><input type="checkbox" id="meta_server_information_authbymail" name="meta_server_information_authbymail"'
						.($server['authbymail'] ? ' checked="checked"' : '').'" /></tr>';
		echo	'</table>';
		echo	'<input type="button" value="update" onclick="jq_meta_server_information_update(' . $_POST['serverid'] . ');" />';
		echo	'<input type="button" value="cancel" onclick="$(\'#jq_information\').html(\'\');" />';
		echo '</div>';
	}

	public static function meta_server_information_update()
	{
		$serverId = isset($_POST['serverid'])?intval($_POST['serverid']):null;
		// user has rights?
		if (PermissionManager::getInstance()->serverCanEditConf($serverId)) {
			if ($serverId != null
					&& isset($_POST['name'])
					&& isset($_POST['allowlogin'])
					&& isset($_POST['allowregistration'])
					&& isset($_POST['forcemail'])
					&& isset($_POST['authbymail'])) {
				$serverId = intval($_POST['serverid']);
				$name = $_POST['name'];
				$allowLogin = $_POST['allowlogin'];
				$allowRegistration = $_POST['allowregistration'];
				$forcemail = $_POST['forcemail'];
				$authByMail = $_POST['authbymail'];

				SettingsManager::getInstance()->setServerInformation($serverId, $name, $allowLogin, $allowRegistration, $forcemail, $authByMail);
			} else {
				MessageManager::addError(TranslationManager::getInstance()->getText('error_missing_values'));
			}
		} else {
			MessageManager::addError('You don’t have permission to do this.');
		}
	}

}
