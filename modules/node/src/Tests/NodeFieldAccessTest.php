<?php
/**
 * @file
 * Contains \Drupal\node\Tests\NodeFieldAccessTest.
 */

namespace Drupal\node\Tests;

use Drupal\Component\Utility\String;
use Drupal\node\Entity\Node;
use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests node field level access.
 *
 * @group node
 */
class NodeFieldAccessTest extends EntityUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  /**
   * Fields that only users with administer nodes permissions can change.
   *
   * @var array
   */
  protected $administrativeFields = array(
    'status',
    'promote',
    'sticky',
    'created',
    'uid',
    'revision_log',
  );

  /**
   * These fields are automatically managed and can not be changed by any user.
   *
   * @var array
   */
  protected $readOnlyFields = array('changed', 'revision_uid', 'revision_timestamp');

  /**
   * Test permissions on nodes status field.
   */
  function testAccessToAdministrativeFields() {

    // An administrator user. No user exists yet, ensure that the first user
    // does not have UID 1.
    $content_admin_user = $this->createUser(array('uid' => 2), array('administer nodes'));

    // Two different editor users.
    $page_creator_user = $this->createUser(array(), array('create page content', 'edit own page content', 'delete own page content'));
    $page_manager_user = $this->createUser(array(), array('create page content', 'edit any page content', 'delete any page content'));

    // An unprivileged user.
    $page_unrelated_user = $this->createUser(array(), array('access content'));

    // List of all users
    $test_users = array(
      $content_admin_user,
      $page_creator_user,
      $page_manager_user,
      $page_unrelated_user,
    );

    // Create three "Basic pages". One is owned by our test-user
    // "page_creator", one by "page_manager", and one by someone else.
    $node1 = Node::create(array(
      'title' => $this->randomMachineName(8),
      'uid' => $page_creator_user->id(),
      'type' => 'page',
    ));
    $node2 = Node::create(array(
      'title' => $this->randomMachineName(8),
      'uid' => $page_manager_user->id(),
      'type' => 'page',
    ));
    $node3 = Node::create(array(
      'title' => $this->randomMachineName(8),
      'type' => 'page',
    ));

    foreach ($this->administrativeFields as $field) {

      // Checks on view operations.
      foreach ($test_users as $account) {
        $may_view = $node1->{$field}->access('view', $account);
        $this->assertTrue($may_view, String::format('Any user may view the field @name.', array('@name' => $field)));
      }

      // Checks on edit operations.
      $may_update = $node1->{$field}->access('edit', $page_creator_user);
      $this->assertFalse($may_update, String::format('Users with permission "edit own page content" is not allowed to the field @name.', array('@name' => $field)));
      $may_update = $node2->{$field}->access('edit', $page_creator_user);
      $this->assertFalse($may_update, String::format('Users with permission "edit own page content" is not allowed to the field @name.', array('@name' => $field)));
      $may_update = $node2->{$field}->access('edit', $page_manager_user);
      $this->assertFalse($may_update, String::format('Users with permission "edit any page content" is not allowed to the field @name.', array('@name' => $field)));
      $may_update = $node1->{$field}->access('edit', $page_manager_user);
      $this->assertFalse($may_update, String::format('Users with permission "edit any page content" is not allowed to the field @name.', array('@name' => $field)));
      $may_update = $node2->{$field}->access('edit', $page_unrelated_user);
      $this->assertFalse($may_update, String::format('Users not having permission "edit any page content" is not allowed to the field @name.', array('@name' => $field)));
      $may_update = $node1->{$field}->access('edit', $content_admin_user) && $node3->status->access('edit', $content_admin_user);
      $this->assertTrue($may_update, String::format('Users with permission "administer nodes" may edit @name fields on all nodes.', array('@name' => $field)));
    }

    foreach ($this->readOnlyFields as $field) {
      // Check view operation.
      foreach ($test_users as $account) {
        $may_view = $node1->{$field}->access('view', $account);
        $this->assertTrue($may_view, String::format('Any user may view the field @name.', array('@name' => $field)));
      }

      // Check edit operation.
      foreach ($test_users as $account) {
        $may_view = $node1->{$field}->access('edit', $account);
        $this->assertFalse($may_view, String::format('No user is not allowed to edit the field @name.', array('@name' => $field)));
      }
    }
  }

}