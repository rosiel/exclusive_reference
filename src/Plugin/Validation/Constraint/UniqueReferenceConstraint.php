<?php

declare(strict_types=1);

namespace Drupal\exclusive_reference\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides an Unique Reference constraint.
 *
 * @Constraint(
 *   id = "UniqueReference",
 *   label = @Translation("Unique Reference", context = "Validation"),
 * )
 *
 * @DCG
 * To apply this constraint on third party entity types implement either
 * hook_entity_base_field_info_alter() or hook_entity_bundle_field_info_alter().
 *
 * @see https://www.drupal.org/node/2015723
 */
final class UniqueReferenceConstraint extends Constraint {

  public string $notUniqueInEntity = 'The referenced entity %target in field %field must be unique, but it is duplicated in this entity.';

  public string $notUniqueInField = 'The referenced entity %target in field %field must be unique, but it is referenced by other entities.';

}
