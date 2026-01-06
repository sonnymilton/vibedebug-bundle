# VibedebugBundle [![Code quality](https://github.com/sonnymilton/vibedebug-bundle/actions/workflows/codequality.yml/badge.svg?branch=main&event=push)](https://github.com/sonnymilton/vibedebug-bundle/actions/workflows/codequality.yml)

VibedebugBundle is a Symfony bundle for “vibe debugging” your applications using AI agents. It automatically collects exceptions and allows you to send them to AI agents for analysis and suggestions directly in Symfony Profiler.

## Features
* Creating of Markdown prompts for debugging via LLM.
* Sending prompts to AI within profiler panel (integration with `symfony/ai-bundle`).
* Export MD prompt by profiler token with controller (available at route `/_vibedebug/{token}/prompt`)

## Installation
1. `composer require milton/vibedebug-bundle --dev`  
The bundle is recommended to use only in a dev environment.
2. Enable the bundle.
    ```php
    // config/bundles.php
    return [
        // ...
        Milton\VibedebugBundle\VibedebugBundle::class => ['dev' => true],
    ];
    ``` 
3. Import routes.
    ```yaml
    # config/routes/dev/vibedebug.yaml
    vibedebug:
        resource: '@VibedebugBundle/config/routes.yaml'
    ```

## Integration with symfony/ai-bundle
Configure you AI agents with `symfony/ai-bundle` so you can send them prompts directly from the vibedebug profiler panel.

### AI Profiler Tools
VibedebugBundle exposes Symfony Profiler data as AI tools, allowing AI agents to fetch profiler information using a profiler token.  
This enables automated debugging workflows, such as analyzing failed requests, inspecting specific data collectors, and correlating exceptions with request context.

### Capabilities
- Export a compact profiler summary by token.
- List available data collectors for a profile.
- Export data from selected collectors.

The tools are registered service tag and can be used by:
- internal Symfony AI agents (`symfony/ai-agent`)
- external agents via MCP (**Symfony AI Mate**)

#### Enabling the AI tools
To make Vibedebug AI tools available for an AI agent, register the tool class in the agent configuration:

```yaml
ai:
    platform:
        ollama:
            host_url: 'http://host.docker.internal:11434'
            http_client: http_client
    agent:
        ollama_debugger:
            platform: 'ai.platform.ollama'
            model:
                class: Symfony\AI\Platform\Bridge\Ollama\Ollama
                name: "qwen3:8b"
            tools:
                - 'vibedebug.profile_exporter' # profile exporter AI tools

```

## Customizing the prompts
To customize the prompt you have to override the templates:
- [system.md.twig](templates/prompt/system.md.twig)
- [exception_detail.md.twig](templates/prompt/exception_detail.md.twig) 


[How to override a template](https://symfony.com/doc/current/bundles/override.html#templates)
