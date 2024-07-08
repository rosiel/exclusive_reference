<?php

namespace Drupal\exclusive_reference\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\field\FieldConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Unique Reference constraint.
 */
final class UniqueReferenceConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;


  /**
   * Unique Reference Validator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container ) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $items, Constraint $constraint): void {
    if (!$items instanceof EntityReferenceFieldItemList) {
      throw new \InvalidArgumentException(
        sprintf('The validated value must be instance of \Drupal\Core\Field\EntityReferenceFieldItemList, %s was given.', get_debug_type($items))
      );
    }

    $existing_targets = [];
    $field_name = $items->getName();

    foreach ($items as $delta => $item) {
      $target_id = $item->getValue()['target_id'];

      # Violation if the same entity appears multiple times.
      if (in_array($target_id, $existing_targets)) {
        $this->context->buildViolation($constraint->notUniqueInEntity,
            ['%target' => $target_id, '%field' => $field_name])
          ->atPath($delta)
          ->addViolation();
      }
      $existing_targets[] = $target_id;

      # Violation if the same entity appears in this field in other entities.
      if (!$this->isUnique($item->getValue(), $items->getFieldDefinition(), $items->getEntity()->id())) {
        $this->context->buildViolation($constraint->notUniqueInField,
          ['%target' => $target_id, '%field' => $field_name])
          ->atPath($delta)
          ->addViolation();
      }
    }
  }

  /**
   * Is this reference unique?
   *
   * @param string $target_id
   *   The id of the target entity.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field to check for duplicates in.
   *
   * @param integer $current_id
   *   The id of the current entity which we're trying to validate.
   *
   */
  protected function isUnique(string $target_id, FieldDefinitionInterface $field_definition, int $current_id): bool {
    // Find entities that reference this target in this field.
    $referencing_entities = $this->findReferencesInField($target_id, $field_definition);

    // Exclude the current entity.
    $referencing_entities = array_filter($referencing_entities, static function ($entity) use ($current_id) {
      return $entity->id() != $current_id;
    });

    // If not unique, return false
    if (count($referencing_entities) > 0) {
      return false;
    }
    return true;
  }

  /**
   * Find all references to a target in a field.
   *
   * @param string $target_id
   *  The id of the target of the reference.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition of the entity reference field to check.
   */
  protected function findReferencesInField($target_id, FieldConfigInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();

    $entities = $this->entityTypeManager->getStorage($entity_type)
      ->loadByProperties([$field_name => $target_id]);

    return $entities;
  }


}
