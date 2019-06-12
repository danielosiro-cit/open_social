<?php

namespace Drupal\social_group_flexible_group\Access;

use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based flexible_group membership and settings.
 */
class FlexibleGroupMemberAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $member_only = $route->getRequirement('_group_member') === 'TRUE';

    $x = 4;

    // Don't interfere if no group was specified.
    $parameters = $route_match->getParameters();
    if (!$parameters->has('group')) {
      return AccessResult::neutral();
    }

    // Don't interfere if the group isn't a real group.
    $group = $parameters->get('group');
    if (!$group instanceof GroupInterface) {
      return AccessResult::neutral();
    }

    $type = $group->getGroupType();
    // Don't interfere if the group isn't a flexible group.
    if (!$type instanceof GroupTypeInterface && $type->id() !== 'flexible_group') {
      return AccessResult::neutral();
    }

    // So we have a flexible group, now lets make our access happen.
    $x = 1;

    return AccessResult::neutral();

    // Only allow access if the user is a member of the group and _flexible_group_member
    // is set to TRUE or the other way around and the settings are allowing it.
//    return AccessResult::allowedIf($group->getMember($account) xor !$member_only);
  }

}
