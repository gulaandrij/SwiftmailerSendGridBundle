<?php

namespace ExpertCoder\Swiftmailer\SendGridBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        /**
         *
         * @var ArrayNodeDefinition $rootNode
         */
        $rootNode = $treeBuilder->root('expert_coder_swiftmailer_send_grid');

        $children = $rootNode->isRequired()->fixXmlConfig('category')->children();
        $this->configureApiKey($children);
        $this->configureCategories($children);
        $this->configureSandboxMode($children);
        $children->end();

        return $treeBuilder;
    }

    /**
     *
     * @param NodeBuilder $nodeBuilder
     */
    private function configureCategories(NodeBuilder $nodeBuilder): void
    {
        // Symfony 3.3+
        if (method_exists($nodeBuilder, 'scalarPrototype')) {
            $nodeBuilder
                ->arrayNode('categories')
                ->scalarPrototype()
                ->end();
        } else {
            $nodeBuilder
                ->arrayNode('categories')
                ->prototype('scalar')
                ->end();
        }
    }

    /**
     *
     * @param NodeBuilder $nodeBuilder
     */
    private function configureApiKey(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('api_key')
            ->isRequired()
            ->end();
    }

    /**
     *
     * @param NodeBuilder $nodeBuilder
     */
    private function configureSandboxMode(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->booleanNode('sandbox_mode')
            ->defaultFalse()
            ->end();
    }
}
