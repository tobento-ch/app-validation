<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);
 
namespace Tobento\App\Validation\Boot;

use Tobento\App\Boot;
use Tobento\App\Migration\Boot\Migration;
use Tobento\Service\Validation\ValidatorInterface;
use Tobento\Service\Validation\Validator as ServiceValidator;
use Tobento\Service\Validation\RulesInterface;
use Tobento\Service\Validation\DefaultRules;
use Tobento\Service\Validation\AutowiringRuleFactory;
use Tobento\Service\Validation\Message\MessagesFactory;
use Tobento\Service\Validation\Message\RuleParametersModifier;
use Tobento\Service\Message\Modifiers;
use Tobento\Service\Message\Modifier;
use Tobento\Service\Translation\TranslatorInterface;

/**
 * Validator
 */
class Validator extends Boot
{
    public const INFO = [
        'boot' => [
            'installs validator translation files',
            'validator and rules interfaces implementation',
        ],
    ];

    public const BOOT = [
        Migration::class,
    ];

    /**
     * Boot application services.
     *
     * @param Migration $migration
     * @return void
     */
    public function boot(Migration $migration): void
    {
        // install validator translations:
        if ($this->app->dirs()->has('trans')) {
            $migration->install(\Tobento\App\Validation\Migration\ValidatorTranslations::class);
        }
        
        $this->app->set(RulesInterface::class, function() {
            return new DefaultRules(
                ruleFactory: new AutowiringRuleFactory($this->app->container()),
            );
        });
        
        $this->app->set(ValidatorInterface::class, function() {
                        
            $modifiers = new Modifiers();
            
            if ($this->app->has(TranslatorInterface::class)) {
                $modifiers->add(new Modifier\Translator(
                    translator: $this->app->get(TranslatorInterface::class),
                    src: 'validator',
                ));
            }
            
            $modifiers->add(new RuleParametersModifier());
            
            if ($this->app->has(TranslatorInterface::class)) {
                $modifiers->add(new Modifier\ParameterTranslator(
                    parameters: [':attribute'],
                    translator: $this->app->get(TranslatorInterface::class),
                    src: '*',
                ));
            }
            
            $modifiers->add(new Modifier\ParameterReplacer());
            
            return new ServiceValidator(
                rules: $this->app->get(RulesInterface::class),
                messagesFactory: new MessagesFactory(
                    modifiers: $modifiers,
                    logger: null,
                ),
            );
        });
    }
}