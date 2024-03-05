<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\SubFieldInterface;
use verbb\formie\base\SubField;
use verbb\formie\events\ModifyFrontEndSubFieldsEvent;
use verbb\formie\events\ModifyNamePrefixOptionsEvent;
use verbb\formie\gql\types\generators\FieldAttributeGenerator;
use verbb\formie\gql\types\input\NameInputType;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Name as NameModel;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\helpers\Component;
use craft\helpers\Html;
use craft\helpers\Json;

use yii\base\Event;
use yii\db\Schema;

use GraphQL\Type\Definition\Type;

class Name extends SubField implements PreviewableFieldInterface
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_FRONT_END_SUBFIELDS = 'modifyFrontEndSubFields';
    public const EVENT_MODIFY_PREFIX_OPTIONS = 'modifyPrefixOptions';



    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Name');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/name/icon.svg';
    }

    public static function getPrefixOptions(): array
    {
        $options = [
            ['label' => Craft::t('formie', 'Mr.'), 'value' => 'mr'],
            ['label' => Craft::t('formie', 'Mrs.'), 'value' => 'mrs'],
            ['label' => Craft::t('formie', 'Ms.'), 'value' => 'ms'],
            ['label' => Craft::t('formie', 'Miss.'), 'value' => 'miss'],
            ['label' => Craft::t('formie', 'Mx.'), 'value' => 'mx'],
            ['label' => Craft::t('formie', 'Dr.'), 'value' => 'dr'],
            ['label' => Craft::t('formie', 'Prof.'), 'value' => 'prof'],
        ];

        $event = new ModifyNamePrefixOptionsEvent([
            'options' => $options,
        ]);

        Event::trigger(static::class, self::EVENT_MODIFY_PREFIX_OPTIONS, $event);

        return $event->options;
    }

    public static function dbType(): string
    {
        return Schema::TYPE_JSON;
    }


    // Properties
    // =========================================================================

    public bool $useMultipleFields = false;

    public bool $prefixEnabled = false;
    public bool $prefixCollapsed = true;
    public ?string $prefixLabel = null;
    public ?string $prefixPlaceholder = null;
    public ?string $prefixDefaultValue = null;
    public ?string $prefixPrePopulate = null;
    public bool $prefixRequired = false;
    public ?string $prefixErrorMessage = null;

    public bool $firstNameEnabled = true;
    public bool $firstNameCollapsed = true;
    public ?string $firstNameLabel = null;
    public ?string $firstNamePlaceholder = null;
    public ?string $firstNameDefaultValue = null;
    public ?string $firstNamePrePopulate = null;
    public bool $firstNameRequired = false;
    public ?string $firstNameErrorMessage = null;

    public bool $middleNameEnabled = false;
    public bool $middleNameCollapsed = true;
    public ?string $middleNameLabel = null;
    public ?string $middleNamePlaceholder = null;
    public ?string $middleNameDefaultValue = null;
    public ?string $middleNamePrePopulate = null;
    public bool $middleNameRequired = false;
    public ?string $middleNameErrorMessage = null;

    public bool $lastNameEnabled = true;
    public bool $lastNameCollapsed = true;
    public ?string $lastNameLabel = null;
    public ?string $lastNamePlaceholder = null;
    public ?string $lastNameDefaultValue = null;
    public ?string $lastNamePrePopulate = null;
    public bool $lastNameRequired = false;
    public ?string $lastNameErrorMessage = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Setuo defaults for some values which can't in in the property definition
        $config['prefixLabel'] = $config['prefixLabel'] ?? Craft::t('formie', 'Prefix');
        $config['firstNameLabel'] = $config['firstNameLabel'] ?? Craft::t('formie', 'First Name');
        $config['middleNameLabel'] = $config['middleNameLabel'] ?? Craft::t('formie', 'Middle Name');
        $config['lastNameLabel'] = $config['lastNameLabel'] ?? Craft::t('formie', 'Last Name');

        $config['instructionsPosition'] = $config['instructionsPosition'] ?? AboveInput::class;

        parent::__construct($config);
    }

    public function hasSubFields(): bool
    {
        if ($this->useMultipleFields) {
            return true;
        }

        return false;
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $value = parent::normalizeValue($value, $element);
        $value = Json::decodeIfJson($value);

        if (is_array($value)) {
            $name = new NameModel($value);
            $name->isMultiple = true;

            return $name;
        }

        return $value;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof NameModel) {
            $value = Json::encode($value);
        }

        return parent::serializeValue($value, $element);
    }

    public function getFrontEndSubFields(mixed $context): array
    {
        $subFields = [];

        $rowConfigs = [
            [
                [
                    'type' => Dropdown::class,
                    'label' => $this->prefixLabel,
                    'handle' => 'prefix',
                    'required' => $this->prefixRequired,
                    'placeholder' => $this->prefixPlaceholder,
                    'errorMessage' => $this->prefixErrorMessage,
                    'defaultValue' => $this->getDefaultValue('prefix'),
                    'labelPosition' => $this->subFieldLabelPosition,
                    'options' => $this->prefixOptions,
                    'inputAttributes' => [
                        [
                            'label' => 'autocomplete',
                            'value' => 'honorific-prefix',
                        ],
                    ],
                ],
                [
                    'type' => SingleLineText::class,
                    'label' => $this->firstNameLabel,
                    'handle' => 'firstName',
                    'required' => $this->firstNameRequired,
                    'placeholder' => $this->firstNamePlaceholder,
                    'errorMessage' => $this->firstNameErrorMessage,
                    'defaultValue' => $this->getDefaultValue('firstName'),
                    'labelPosition' => $this->subFieldLabelPosition,
                    'inputAttributes' => [
                        [
                            'label' => 'autocomplete',
                            'value' => 'given-name',
                        ],
                    ],
                ],
                [
                    'type' => SingleLineText::class,
                    'label' => $this->middleNameLabel,
                    'handle' => 'middleName',
                    'required' => $this->middleNameRequired,
                    'placeholder' => $this->middleNamePlaceholder,
                    'errorMessage' => $this->middleNameErrorMessage,
                    'defaultValue' => $this->getDefaultValue('middleName'),
                    'labelPosition' => $this->subFieldLabelPosition,
                    'inputAttributes' => [
                        [
                            'label' => 'autocomplete',
                            'value' => 'additional-name',
                        ],
                    ],
                ],
                [
                    'type' => SingleLineText::class,
                    'label' => $this->lastNameLabel,
                    'handle' => 'lastName',
                    'required' => $this->lastNameRequired,
                    'placeholder' => $this->lastNamePlaceholder,
                    'errorMessage' => $this->lastNameErrorMessage,
                    'defaultValue' => $this->getDefaultValue('lastName'),
                    'labelPosition' => $this->subFieldLabelPosition,
                    'inputAttributes' => [
                        [
                            'label' => 'autocomplete',
                            'value' => 'family-name',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($rowConfigs as $key => $rowConfig) {
            foreach ($rowConfig as $config) {
                $handle = $config['handle'];
                $enabledProp = "{$handle}Enabled";

                if ($this->$enabledProp) {
                    $subField = Component::createComponent($config, FieldInterface::class);

                    // Ensure we set the parent field instance to handle the nested nature of subfields
                    $subField->setParentField($this);

                    $subFields[$key][] = $subField;
                }
            }
        }

        $event = new ModifyFrontEndSubFieldsEvent([
            'field' => $this,
            'rows' => $subFields,
        ]);

        Event::trigger(static::class, self::EVENT_MODIFY_FRONT_END_SUBFIELDS, $event);

        return $event->rows;
    }

    public function getSubFieldOptions(): array
    {
        return [
            [
                'label' => Craft::t('formie', 'Prefix'),
                'handle' => 'prefix',
            ],
            [
                'label' => Craft::t('formie', 'First Name'),
                'handle' => 'firstName',
            ],
            [
                'label' => Craft::t('formie', 'Middle Name'),
                'handle' => 'middleName',
            ],
            [
                'label' => Craft::t('formie', 'Last Name'),
                'handle' => 'lastName',
            ],
        ];
    }

    public function validateRequiredFields(ElementInterface $element): void
    {
        if (!$this->useMultipleFields) {
            return;
        }

        parent::validateRequiredFields($element);
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/name/preview', [
            'field' => $this,
        ]);
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'prefixOptions' => [
                'name' => 'prefixOptions',
                'type' => Type::listOf(FieldAttributeGenerator::generateType()),
            ],
        ]);
    }

    public function defineGeneralSchema(): array
    {
        $fields = [
            SchemaHelper::labelField(),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Use Multiple Name Fields'),
                'help' => Craft::t('formie', 'Whether this field should use multiple fields for users to enter their details.'),
                'name' => 'useMultipleFields',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
                'if' => '$get(useMultipleFields).value != true',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
                'variables' => 'userVariables',
                'if' => '$get(useMultipleFields).value != true',
            ]),
        ];

        $toggleBlocks = [];

        foreach ($this->getSubFieldOptions() as $key => $nestedField) {
            $subFields = [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Label'),
                    'help' => Craft::t('formie', 'The label that describes this field.'),
                    'name' => $nestedField['handle'] . 'Label',
                    'validation' => 'requiredIf:' . $nestedField['handle'] . 'Enabled',
                    'required' => true,
                ]),

                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Placeholder'),
                    'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                    'name' => $nestedField['handle'] . 'Placeholder',
                ]),
            ];

            if ($nestedField['handle'] === 'prefix') {
                $subFields[] = SchemaHelper::selectField([
                    'label' => Craft::t('formie', 'Default Value'),
                    'help' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                    'name' => $nestedField['handle'] . 'DefaultValue',
                    'options' => array_merge(
                        [['label' => Craft::t('formie', 'Select an option'), 'value' => '']],
                        static::getPrefixOptions()
                    ),
                ]);
            } else {
                $subFields[] = SchemaHelper::variableTextField([
                    'label' => Craft::t('formie', 'Default Value'),
                    'help' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                    'name' => $nestedField['handle'] . 'DefaultValue',
                    'variables' => 'userVariables',
                ]);
            }

            $toggleBlock = SchemaHelper::toggleBlock([
                'blockLabel' => $nestedField['label'],
                'blockHandle' => $nestedField['handle'],
            ], $subFields);

            $toggleBlock['if'] = '$get(useMultipleFields).value';

            $toggleBlocks[] = $toggleBlock;
        }

        $fields[] = SchemaHelper::toggleBlocks([
            'subFields' => $this->getSubFieldOptions(),
        ], $toggleBlocks);

        return $fields;
    }

    public function defineSettingsSchema(): array
    {
        $fields = [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Required Field'),
                'help' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
                'name' => 'required',
                'if' => '$get(useMultipleFields).value != true',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Error Message'),
                'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                'name' => 'errorMessage',
                'if' => '$get(required).value && $get(useMultipleFields).value != true',
            ]),
            SchemaHelper::prePopulate([
                'if' => '$get(useMultipleFields).value != true',
            ]),
            SchemaHelper::includeInEmailField(),
        ];

        foreach ($this->getSubFieldOptions() as $key => $nestedField) {
            $subFields = [
                SchemaHelper::lightswitchField([
                    'label' => Craft::t('formie', 'Required Field'),
                    'help' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
                    'name' => $nestedField['handle'] . 'Required',
                ]),
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Error Message'),
                    'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                    'name' => $nestedField['handle'] . 'ErrorMessage',
                    'if' => '$get(' . $nestedField['handle'] . 'Required).value',
                ]),
                SchemaHelper::prePopulate([
                    'name' => $nestedField['handle'] . 'PrePopulate',
                ]),
            ];

            $toggleBlock = SchemaHelper::toggleBlock([
                'blockLabel' => $nestedField['label'],
                'blockHandle' => $nestedField['handle'],
                'showToggle' => false,
                'showEnabled' => false,
            ], $subFields);

            $toggleBlock['if'] = '$get(' . $nestedField['handle'] . 'Enabled).value && $get(useMultipleFields).value';

            $fields[] = $toggleBlock;
        }

        return $fields;
    }

    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::subFieldLabelPosition([
                'if' => '$get(useMultipleFields).value',
            ]),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::enableContentEncryptionField(),
        ];
    }

    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function getContentGqlMutationArgumentType(): array|Type
    {
        if ($this->useMultipleFields) {
            return NameInputType::getType($this);
        }

        return Type::string();
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;
        $errors = $context['errors'] ?? null;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($this->useMultipleFields) {
            if ($key === 'fieldContainer') {
                return new HtmlTag('fieldset', [
                    'class' => 'fui-fieldset fui-subfield-fieldset',
                ]);
            }

            if ($key === 'fieldLabel') {
                $labelPosition = $context['labelPosition'] ?? null;

                return new HtmlTag('legend', [
                    'class' => [
                        'fui-legend',
                    ],
                    'data' => [
                        'fui-sr-only' => $labelPosition instanceof HiddenPosition ? true : false,
                    ],
                ]);
            }
        }

        if ($key === 'fieldInput') {
            return new HtmlTag('input', [
                'type' => 'text',
                'id' => $id,
                'class' => [
                    'fui-input',
                    $errors ? 'fui-error' : false,
                ],
                'name' => $this->getHtmlName(),
                'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                'autocomplete' => 'name',
                'required' => $this->required ? true : null,
                'data' => [
                    'fui-id' => $dataId,
                    'fui-message' => Craft::t('formie', $this->errorMessage) ?: null,
                ],
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ], $this->getInputAttributes());
        }

        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [
            ['subFieldLabelPosition'],
            'in',
            'range' => Formie::$plugin->getFields()->getLabelPositions(),
            'skipOnEmpty' => true,
        ];

        return $rules;
    }

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/name/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'element' => $element,
        ]);
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        if ($this->useMultipleFields) {
            $values = [];

            foreach ($this->getSubFieldOptions() as $subField) {
                if ($this->{$subField['handle'] . 'Enabled'}) {
                    $values[$this->getExportLabel($element) . ': ' . $subField['label']] = $value[$subField['handle']] ?? '';
                }
            }

            return $values;
        }

        return $value;
    }

}
