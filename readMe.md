# rkw_outcome
## Features
Extension for processing outcome surveys.

## Structure and process

If a event reservation or an order is confirmed, the fired signal will eventually initiate the process of creating a survey request associated with the corrsponding reservation or order.

The survey request will be created only, if a the event reservation or order fits to an existing survey configuration, which is set in the backend. The following parameters must match:

* targetGroup
* product or event

If these parameters do match the survey request is created immediately. A scheduled task will take care of processing these pending survey requests. Therefore a corresponding task ```SurveyRequestCommandController->processSurveyRequestsCommand()``` has to be set in the scheduler. It takes the following parameters:

* ```checkPeriod``` (range of time containing the potentially processable event reservations or orders - in seconds)
* ```maxSurveysPerPeriodAndFrontendUser``` (max number of request notifications to be sent to a user within the ```checkPeriod```)
* ```urveyWaitingTime``` (select only survey requests, which have been shipped at least this amount of time ago)

If there are pending survey requests they will be processed. In case there are multiple requests pending, meaning there are at least two products or events associated with survey configuration, the process will randomly select one out of these and store it to the containing survey request. Each of the matching survey requests will get a timestamp to mark them as notified.

In the same time a notification to the corresponding frontenduser will be sent to provide them a link to the selected survey. This link will sent them to a page containing a regular survey plugin, but using the uid of the selected survey in combination with an enhanced real url configuration, the frontenduser will get the corresponding survey and can go along.

## Setup

You should create a backend folder to store the outcome records. The uid of this folder should be set as constant ```storagePid```.

The extension relies on the extensions rkw_events and rkw_shop. Both extensions provide an additional constant ```targetGroupsPid```. This constant should be used to restrict the selection of targetgroups in the order or reservation form to the selected uid of the parent ```sys_category```.

Furthermore you should set the constant ```surveyShowPid``` to the page uid containing your survey plugin. This will be used in rendering the link to the corresponding survey within the notification mail.

Necessary enhancement of the real url configuration:

```php
                //===============================================
                // Survey
                'tx-rkw-survey' => array (
                    array(
                        'GETvar' => 'tx_rkwsurvey_survey[controller]',
                        'valueMap' => array(
                            'survey' => 'Survey',
                        ),
                    ),
                    array(
                        'GETvar' => 'tx_rkwsurvey_survey[action]' ,
                    ),
                    // look-up table - param has to be set in cHash-ignore in Install-Tool!
                    array(
                        'GETvar' => 'tx_rkwsurvey_survey[survey]' ,
                        'lookUpTable' => [
                            'table' => 'tx_rkwsurvey_domain_model_survey',
                            'id_field' => 'uid',
                            'alias_field' => 'CONCAT(name, "-", uid)',
                            'addWhereClause' => ' AND NOT deleted AND NOT hidden',
                            'useUniqueCache' => 1,
                            'useUniqueCache_conf' => [
                                'strtolower' => 1,
                                'spaceCharacter' => '-',
                            ],
                        ],
                    ),
                    array(
                        'GETvar' => 'tx_rkwsurvey_survey[surveyResult]',
                    ),
                ),
```

## Testing

Some of the tests rely on setting the environment time to correspond to different time-based scenarios. So please make sure to install the following package in your root folder by running:

``composer require-dev nesbot/carbon``

Status of editing: 2023-04-05
