# think-workflow
Use the Symfony Workflow component in Thinkphp
## 安装
``` shell
composer require yuanzhihai/think-workflow
```
## 使用
### 配置

配置您的工作流程config/workflow.php
``` php
<?php

// Full workflow, annotated.
return [
    // Name of the workflow is the key
    'straight' => [
        'type' => 'workflow', // or 'state_machine', defaults to 'workflow' if omitted
        // The marking store can be omitted, and will default to 'multiple_state'
        // for workflow and 'single_state' for state_machine if the type is omitted
        'marking_store' => [
            'property' => 'marking', // this is the property on the model, defaults to 'marking'
            'class' => MethodMarkingStore::class, // optional, uses EloquentMethodMarkingStore by default (for Eloquent models)
        ],
        // optional top-level metadata
        'metadata' => [
            // any data
        ],
        'supports' => ['app\BlogPost'], // objects this workflow supports
        // Specifies events to dispatch (only in 'workflow', not 'state_machine')
        // - set `null` to dispatch all events (default, if omitted)
        // - set to empty array (`[]`) to dispatch no events
        // - set to array of events to dispatch only specific events
        // Note that announce will dispatch a guard event on the next transition
        // (if announce isn't dispatched the next transition won't guard until checked/applied)
        'events_to_dispatch' => [
           Symfony\Component\Workflow\WorkflowEvents::ENTER,
           Symfony\Component\Workflow\WorkflowEvents::LEAVE,
           Symfony\Component\Workflow\WorkflowEvents::TRANSITION,
           Symfony\Component\Workflow\WorkflowEvents::ENTERED,
           Symfony\Component\Workflow\WorkflowEvents::COMPLETED,
           Symfony\Component\Workflow\WorkflowEvents::ANNOUNCE,
        ],
        'places' => ['draft', 'review', 'rejected', 'published'],
        'initial_places' => ['draft'], // defaults to the first place if omitted
        'transitions' => [
            'to_review' => [
                'from' => 'draft',
                'to' => 'review',
                // optional transition-level metadata
                'metadata' => [
                    // any data
                ]
            ],
            'publish' => [
                'from' => 'review',
                'to' => 'published'
            ],
            'reject' => [
                'from' => 'review',
                'to' => 'rejected'
            ]
        ],
    ]
];
```

使用WorkflowTrait内部支持的类
``` php
<?php

namespace app;

use think\Model;
use yuanzhihai\Think\Workflow\Traits\WorkflowTrait;

class BlogPost extends Model
{
  use WorkflowTrait;

}
```
### 用法
``` php
<?php

use app\BlogPost;
use Ting\Think\Workflow\Facades\Workflow;

$post = BlogPost::find(1);
$workflow = Workflow::get($post);
// if more than one workflow is defined for the BlogPost class
$workflow = Workflow::get($post, $workflowName);
// or get it directly from the trait
$workflow = $post->workflow_get();
// if more than one workflow is defined for the BlogPost class
$workflow = $post->workflow_get($workflowName);

$workflow->can($post, 'publish'); // False
$workflow->can($post, 'to_review'); // True
$transitions = $workflow->getEnabledTransitions($post);

// Apply a transition
$workflow->apply($post, 'to_review');
$post->save(); // Don't forget to persist the state

// Get the workflow directly

// Using the WorkflowTrait
$post->workflow_can('publish'); // True
$post->workflow_can('to_review'); // False

// Get the post transitions
foreach ($post->workflow_transitions() as $transition) {
    echo $transition->getName();
}

// Apply a transition
$post->workflow_apply('publish');
$post->save();
```
### Symfony 工作流程使用
一旦您拥有了底层的 Symfony 工作流程组件，您就可以做任何您想做的事情，就像在 Symfony 中一样。下面提供了几个示例，但请务必查看Symfony 文档以更好地了解这里发生的情况。
``` php
<?php

use App\Blogpost;
use Ting\Think\Workflow\Facades\Workflow;

$post = BlogPost::find(1);
$workflow = $post->workflow_get();

// Get the current places
$places = $workflow->getMarking($post)->getPlaces();

// Get the definition
$definition = $workflow->getDefinition();

// Get the metadata
$metadata = $workflow->getMetadataStore();
// or get a specific piece of metadata
$workflowMetadata = $workflow->getMetadataStore()->getWorkflowMetadata();
$placeMetadata = $workflow->getMetadataStore()->getPlaceMetadata($place); // string place name
$transitionMetadata = $workflow->getMetadataStore()->getTransitionMetadata($transition); // transition object
// or by key
$otherPlaceMetadata = $workflow->getMetadataStore()->getMetadata('max_num_of_words', 'draft');
```
### 使用事件
该包提供了转换期间触发的事件列表
``` php
    yuanzhihai\Think\Workflow\Events\Guard
    yuanzhihai\Think\Workflow\Events\Leave
    yuanzhihai\Think\Workflow\Events\Transition
    yuanzhihai\Think\Workflow\Events\Enter
    yuanzhihai\Think\Workflow\Events\Entered
```
### 工作流与状态机
当使用多状态工作流程时，有必要区分可以转换到一个位置的多个位置的数组，或者恰好多个位置的主题转换到一个位置的情况。由于配置是一个 PHP 数组，因此您必须将后一种情况“嵌套”到一个数组中，以便它使用位置数组构建转换，而不是循环遍历单个位置。
示例 1. 恰好有两个位置转换为一个位置
在此示例中，草稿必须content_approved同时存在legal_approved于

``` php
<?php

return [
    'straight' => [
        'type' => 'workflow',
        'metadata' => [
            'title' => 'Blog Publishing Workflow',
        ],
        'marking_store' => [
            'property' => 'currentPlace'
        ],
        'supports' => ['App\BlogPost'],
        'places' => [
            'draft',
            'content_review',
            'content_approved',
            'legal_review',
            'legal_approved',
            'published'
        ],
        'transitions' => [
            'to_review' => [
                'from' => 'draft',
                'to' => ['content_review', 'legal_review'],
            ],
            // ... transitions to "approved" states here
            'publish' => [
                'from' => [ // note array in array
                    ['content_review', 'legal_review']
                ],
                'to' => 'published'
            ],
            // ...
        ],
    ]
];
```
示例 2. 两个位置中的任意一个转换为一个
在此示例中，草稿可以从 EITHER content_approvedOR转换legal_approved为published
  ``` php
<?php

return [
    'straight' => [
        'type' => 'workflow',
        'metadata' => [
            'title' => 'Blog Publishing Workflow',
        ],
        'marking_store' => [
            'property' => 'currentPlace'
        ],
        'supports' => ['App\BlogPost'],
        'places' => [
            'draft',
            'content_review',
            'content_approved',
            'legal_review',
            'legal_approved',
            'published'
        ],
        'transitions' => [
            'to_review' => [
                'from' => 'draft',
                'to' => ['content_review', 'legal_review'],
            ],
            // ... transitions to "approved" states here
            'publish' => [
                'from' => [
                    'content_review',
                    'legal_review'
                ],
                'to' => 'published'
            ],
            // ...
        ],
    ]
];
```
### Dump Workflows
Symfony 工作流程使用 GraphvizDumper 来创建工作流程图像。您可能需要安装Graphvizdot的命令
```php
php think workflow:dump workflow_name --class app\\BlogPost
```
您可以使用该选项更改图像格式--format。默认格式为 png。
```php
    php think workflow:dump workflow_name --format=jpg
```
如果您想输出到与 root 不同的目录，您可以使用--disk和--path选项设置存储磁盘（local默认情况下）和路径（root_path()默认情况下）。
```php
    php think workflow:dump workflow-name --class=App\\BlogPost --disk=s3 --path="workflows/diagrams/"
```
### 在跟踪模式下使用
如果您通过某种动态方式（可能通过数据库）加载工作流定义，您很可能希望打开注册表跟踪。这将使您能够查看已加载的内容，以防止或忽略重复的工作流程定义。
在配置workflow_registry.php文件中设置track_loaded为true 
```php
    <?php

return [

    /**
     * When set to true, the registry will track the workflows that have been loaded.
     * This is useful when you're loading from a DB, or just loading outside of the
     * main config files.
     */
    'track_loaded' => false,

    /**
     * Only used when track_loaded = true
     *
     * When set to true, a registering a duplicate workflow will be ignored (will not load the new definition)
     * When set to false, a duplicate workflow will throw a DuplicateWorkflowException
     */
    'ignore_duplicates' => false,

];
```
您可以使用addFromArray工作流注册表上的方法动态加载工作流
```php
    <?php

    /**
     * Load the workflow type definition into the registry
     */
    protected function loadWorkflow()
    {
        $registry = app()->make('workflow');
        $workflowName = 'straight';
        $workflowDefinition = [
            // Workflow definition here
            // (same format as config/symfony docs)
            // This should be the definition only,
            // not including the key for the name.
            // See note below on initial_places for an example.
        ];

        $registry->addFromArray($workflowName, $workflowDefinition);

        // or if catching duplicates

        try {
            $registry->addFromArray($workflowName, $workflowDefinition);
        } catch (DuplicateWorkflowException $e) {
            // already loaded
        }
    }
```
注意：动态工作流程没有持久性，此包假设您以某种方式存储这些工作流程（数据库等）。要使用动态工作流程，您需要在使用之前加载工作流程。上述方法loadWorkflow()可以绑定到模型事件中。

initial_places如果它不是“位置”列表中的第一个位置，您还可以在工作流程定义中指定。
```php
    <?php
return [
    'type' => 'workflow', // or 'state_machine'
    'metadata' => [
        'title' => 'Blog Publishing Workflow',
    ],
    'marking_store' => [
        'property' => 'currentPlace'
    ],
    'supports' => ['App\BlogPost'],
    'places' => [
        'review',
        'rejected',
        'published',
        'draft', => [
            'metadata' => [
                'max_num_of_words' => 500,
            ]
        ]
    ],
    'initial_places' => 'draft', // or set to an array if multiple initial places
    'transitions' => [
        'to_review' => [
            'from' => 'draft',
            'to' => 'review',
            'metadata' => [
                'priority' => 0.5,
            ]
        ],
        'publish' => [
            'from' => 'review',
            'to' => 'published'
        ],
        'reject' => [
            'from' => 'review',
            'to' => 'rejected'
        ]
    ],
];
```




