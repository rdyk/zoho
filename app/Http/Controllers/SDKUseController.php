<?php


namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;


use com\zoho\api\authenticator\OAuthToken;
use com\zoho\api\authenticator\TokenType;
use com\zoho\api\authenticator\store\DBStore;
use com\zoho\api\authenticator\store\FileStore;
use com\zoho\crm\api\Initializer;
use com\zoho\crm\api\UserSignature;
use com\zoho\crm\api\dc\USDataCenter;
use com\zoho\api\logger\Logger;
use com\zoho\api\logger\Levels;
use com\zoho\crm\api\SDKConfigBuilder;
use com\zoho\crm\api\HeaderMap;
use com\zoho\crm\api\ParameterMap;
use com\zoho\crm\api\record\ActionWrapper;
use com\zoho\crm\api\record\BodyWrapper;
use com\zoho\crm\api\record\FileBodyWrapper;
use com\zoho\crm\api\record\FileDetails;
use com\zoho\crm\api\record\InventoryLineItems;
use com\zoho\crm\api\record\RecordOperations;
use com\zoho\crm\api\record\RecurringActivity;
use com\zoho\crm\api\record\RemindAt;
use com\zoho\crm\api\record\SuccessResponse;
use com\zoho\crm\api\tags\Tag;
use com\zoho\crm\api\record\{Cases, Field, Solutions, Accounts, Campaigns, Calls, Leads, Tasks, Deals, Sales_Orders, Contacts, Quotes, Events, Price_Books, Purchase_Orders, Vendors};
use com\zoho\crm\api\util\Choice;
use com\zoho\crm\api\record\Record;



class SDKUseController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    //grant_token    1000.3a0b76abd17b84fc9dc8436ddaf91510.5fb118dd7b16bc31a7722ee13ef86784
    //refresh_token

    public function __construct()
    {


        $configuration = array(
            "client_id" => '1000.XPIXLCC8EL4HQPJ62PUR6OR26PO5EJ',
            "client_secret" => 'b37468bbc6fb68b34d3b50f3fa34a4abc3771af174',
            "redirect_uri" => 'homestead.test/sdk',
            "currentUserEmail" => 'rdykukha@gmail.com'
        );

   //     ZCRMRestClient::initialize();
   //     $oAuthClient = ZohoOAuth::getClientInstance();
    //    $grantToken = "1000.abc25c6cf5bd0e66948bff594df8359f.b72b2153a41771dc49c21b39eb19634d";
    //    $oAuthTokens = $oAuthClient->generateAccessToken($grantToken);
    //    session()->put('sdk_token', $oAuthTokens);
    //    dump($oAuthTokens);

        $logger = Logger::getInstance(Levels::INFO, storage_path() . "/php_sdk_log.log");
        $user = new UserSignature("rdykukha@gmail.com");
        $environment = USDataCenter::PRODUCTION();
        $token = new OAuthToken(
            "1000.XPIXLCC8EL4HQPJ62PUR6OR26PO5EJ",
            "b37468bbc6fb68b34d3b50f3fa34a4abc3771af174",
            "1000.977e03d3eb6f3eca5acfae1bace96fec.a814513ed5e1cf998753d24acfa76b31",
            TokenType::GRANT,
            "homestead.test/sdk"
        );
        $tokenstore = new FileStore(storage_path() . '/token.txt');

        $autoRefreshFields = false;
        $pickListValidation = false;
        $enableSSLVerification = false;
        $sdkConfig = (new SDKConfigBuilder())
            ->setAutoRefreshFields($autoRefreshFields)
            ->setPickListValidation($pickListValidation)
           // ->setSSLVerification($enableSSLVerification)
            ->build();

        $resourcePath = storage_path(). "/phpsdk-application";
        //$requestProxy = new RequestProxy("proxyHost", "proxyPort", "proxyUser", "password");

        //Initializer::initialize($user, $environment, $token, $tokenstore, $sdkConfig, $resourcePath, $logger, $requestProxy);
        try {
            Initializer::initialize($user, $environment, $token, $tokenstore, $sdkConfig, $resourcePath, $logger);
        } catch (\Exception $e) {
            dump($e);
        }

    }

    public function index()
    {



        //Get instance of RecordOperations Class that takes moduleAPIName as parameter
        $recordOperations = new RecordOperations();

        //Get instance of BodyWrapper Class that will contain the request body
        $bodyWrapper = new BodyWrapper();


        try {

            //self::getRecord('Deals', '4806354000000328278', storage_path() . '/phpsdk-application');
            //self::getRecord('Leads', '4806354000000328423', storage_path() . '/phpsdk-application');

            $field = new Field("");

            $deal = new Record();

            $deal->addFieldValue(Deals::DealName(), 'New Deal Name2');
            $deal->addFieldValue(Deals::Amount(), 99.00);
            $deal->addFieldValue(Deals::Description(), 'New Deal Descriptin');

            $contactName = new Record();
            $contactName->addFieldValue(Contacts::id(), "4806354000000328195");

            $deal->addFieldValue(Deals::ContactName(), $contactName);
            $deal->addFieldValue(Deals::Stage(), new Choice('Qualification'));
            $bodyWrapper->setData([$deal]);

            $trigger = array("approval", "workflow", "blueprint");
            //$bodyWrapper->setTrigger($trigger);
            $response = $recordOperations->createRecords('Deals',$bodyWrapper);

            dump($response);


            $deal_id = $this->getOneNewIdFromResponse($response);
            //$deal_id = '4806354000000360001';


            $task = new Record();
            $task->addFieldValue(Tasks::Subject(), "Subject Test Task2");
            $task->addFieldValue(Tasks::Description(), "Test Task");
            $task->addKeyValue("Currency",new Choice("INR"));


            $task->addFieldValue(Tasks::Status(),new Choice("Waiting for input"));
            $task->addFieldValue(Tasks::DueDate(), new \DateTime('2021-03-08'));
            $task->addFieldValue(Tasks::Priority(),new Choice("High"));
            $task->addKeyValue('$se_module', "Deals");

            $whatId = new Record();
            $whatId->setId($deal_id);
            $task->addFieldValue(Tasks::WhatId(), $whatId);

            $bodyWrapper->setData([$task]);
            $response = $recordOperations->createRecords('Tasks',$bodyWrapper);

            dump($response);

        } catch (\Exception $e) {
            dump($e);
        }
    }


    public function getOneNewIdFromResponse($response)
    {

        dump($response);

        if ($response == null) {
            throw new \RuntimeException('response is null');
        };


        //Get the status code from response
        echo("Status Code: " . $response->getStatusCode() . "\n");


        $obj = $response->getObject();
        $obj = $obj->getData();
        $obj = $obj[0];
        $obj = $obj->getDetails();
        $obj = $obj['id'];

        //dump($obj);

        return $obj;

    }

    //***********************************************TEST******************************************************************

    public static function getRecord(string $moduleAPIName, string $recordId, string $destinationFolder)
    {
        //example
        //$moduleAPIName = "Leads";
        //$recordId = "3477061000005177002";

        //Get instance of RecordOperations Class
        $recordOperations = new RecordOperations();

        //Get instance of ParameterMap Class
        $paramInstance = new ParameterMap();

        // $paramInstance->add(GetRecordParam::approved(), "false");

        // $paramInstance->add(GetRecordParam::converted(), "false");

        // $fieldNames = array("Deal_Name", "Company");

        // foreach($fieldNames as $fieldName)
        // {
        // 	$paramInstance->add(GetRecordParam::fields(), $fieldName);
        // }

        // $startdatetime = date_create("2020-06-27T15:10:00");

        // $paramInstance->add(GetRecordParam::startDateTime(), $startdatetime);

        // $enddatetime = date_create("2020-06-29T15:10:00");

        // $paramInstance->add(GetRecordParam::endDateTime(), $enddatetime);

        // $paramInstance->add(GetRecordParam::territoryId(), "3477061000003051357");

        // $paramInstance->add(GetRecordParam::includeChild(), true);

        $headerInstance = new HeaderMap();

        // $ifmodifiedsince = date_create("2020-06-02T11:03:06+05:30")->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        // $headerInstance->add(GetRecordHeader::IfModifiedSince(), $ifmodifiedsince);

        //Call getRecord method that takes paramInstance, moduleAPIName and recordID as parameter


        $response = $recordOperations->getRecord( $recordId,$moduleAPIName,$paramInstance, $headerInstance);



        if($response != null)
        {
            //Get the status code from response
            echo("Status code " . $response->getStatusCode() . "\n");

            if(in_array($response->getStatusCode(), array(204, 304)))
            {
                echo($response->getStatusCode() == 204? "No Content\n" : "Not Modified\n");

                return;
            }



            //dump($response->isExpected());

            if($response->isExpected())
            {


                //Get object from response
                $responseHandler = $response->getObject();
                //dump(get_class($responseHandler));
                //dump($responseHandler instanceof \com\zoho\crm\api\record\ResponseWrapper);



                if($responseHandler instanceof \com\zoho\crm\api\record\ResponseWrapper)
                {


                    //Get the received ResponseWrapper instance
                    $responseWrapper = $responseHandler;

                    //Get the list of obtained Record instances
                    $records = $responseWrapper->getData();

                    //dump($records);

                    if($records != null)
                    {
                        $recordClass = 'com\zoho\crm\api\record\Record';

                        foreach($records as $record)
                        {
                            //Get the ID of each Record
                            echo("Record ID: " . $record->getId() . "\n");

                            //Get the createdBy User instance of each Record
                            $createdBy = $record->getCreatedBy();

                            //Check if createdBy is not null
                            if($createdBy != null)
                            {
                                //Get the ID of the createdBy User
                                echo("Record Created By User-ID: " . $createdBy->getId() . "\n");

                                //Get the name of the createdBy User
                                echo("Record Created By User-Name: " . $createdBy->getName() . "\n");

                                //Get the Email of the createdBy User
                                echo("Record Created By User-Email: " . $createdBy->getEmail() . "\n");
                            }

                            //Get the CreatedTime of each Record
                            echo("Record CreatedTime: ");

                            print_r($record->getCreatedTime());

                            echo("\n");

                            //Get the modifiedBy User instance of each Record
                            $modifiedBy = $record->getModifiedBy();

                            //Check if modifiedBy is not null
                            if($modifiedBy != null)
                            {
                                //Get the ID of the modifiedBy User
                                echo("Record Modified By User-ID: " . $modifiedBy->getId() . "\n");

                                //Get the name of the modifiedBy User
                                echo("Record Modified By User-Name: " . $modifiedBy->getName() . "\n");

                                //Get the Email of the modifiedBy User
                                echo("Record Modified By User-Email: " . $modifiedBy->getEmail() . "\n");
                            }

                            //Get the ModifiedTime of each Record
                            echo("Record ModifiedTime: ");

                            print_r($record->getModifiedTime());

                            print_r("\n");

                            //Get the list of Tag instance each Record
                            $tags = $record->getTag();

                            //Check if tags is not null
                            if($tags != null)
                            {
                                foreach($tags as $tag)
                                {
                                    //Get the Name of each Tag
                                    echo("Record Tag Name: " . $tag->getName() . "\n");

                                    //Get the Id of each Tag
                                    echo("Record Tag ID: " . $tag->getId() . "\n");
                                }
                            }

                            //To get particular field value
                            echo("Record Field Value: " . $record->getKeyValue("Last_Name") . "\n");// FieldApiName

                            echo("Record KeyValues : \n" );

                            //Get the KeyValue map
                            foreach($record->getKeyValues() as $keyName => $value)
                            {


                                //dump($keyName);
                                //dump($value);

                                if($value != null)
                                {


                                    if((is_array($value) && sizeof($value) > 0) && isset($value[0]))
                                    {


//                                        dump(get_class($value[0]));


                                        if($value[0] instanceof FileDetails)
                                        {
                                            $fileDetails = $value;

                                            foreach($fileDetails as $fileDetail)
                                            {
                                                //Get the Extn of each FileDetails
                                                echo("Record FileDetails Extn: " . $fileDetail->getExtn() . "\n");

                                                //Get the IsPreviewAvailable of each FileDetails
                                                echo("Record FileDetails IsPreviewAvailable: " . $fileDetail->getIsPreviewAvailable() . "\n");

                                                //Get the DownloadUrl of each FileDetails
                                                echo("Record FileDetails DownloadUrl: " . $fileDetail->getDownloadUrl() . "\n");

                                                //Get the DeleteUrl of each FileDetails
                                                echo("Record FileDetails DeleteUrl: " . $fileDetail->getDeleteUrl() . "\n");

                                                //Get the EntityId of each FileDetails
                                                echo("Record FileDetails EntityId: " . $fileDetail->getEntityId() . "\n");

                                                //Get the Mode of each FileDetails
                                                echo("Record FileDetails Mode: " . $fileDetail->getMode() . "\n");

                                                //Get the OriginalSizeByte of each FileDetails
                                                echo("Record FileDetails OriginalSizeByte: " . $fileDetail->getOriginalSizeByte() . "\n");

                                                //Get the PreviewUrl of each FileDetails
                                                echo("Record FileDetails PreviewUrl: " . $fileDetail->getPreviewUrl() . "\n");

                                                //Get the FileName of each FileDetails
                                                echo("Record FileDetails FileName: " . $fileDetail->getFileName() . "\n");

                                                //Get the FileId of each FileDetails
                                                echo("Record FileDetails FileId: " . $fileDetail->getFileId() . "\n");

                                                //Get the AttachmentId of each FileDetails
                                                echo("Record FileDetails AttachmentId: " . $fileDetail->getAttachmentId() . "\n");

                                                //Get the FileSize of each FileDetails
                                                echo("Record FileDetails FileSize: " . $fileDetail->getFileSize() . "\n");

                                                //Get the CreatorId of each FileDetails
                                                echo("Record FileDetails CreatorId: " . $fileDetail->getCreatorId() . "\n");

                                                //Get the LinkDocs of each FileDetails
                                                echo("Record FileDetails LinkDocs: " . $fileDetail->getLinkDocs() . "\n");
                                            }
                                        }
                                        else if($value[0] instanceof Choice)
                                        {
                                            $choice = $value;

                                            foreach($choice as $choiceValue)
                                            {
                                                echo("Record " . $keyName . " : " . $choiceValue->getValue() . "\n");
                                            }
                                        }
                                        else if($value[0] instanceof InventoryLineItems)
                                        {
                                            $productDetails = $value;

                                            foreach($productDetails as $productDetail)
                                            {
                                                $lineItemProduct = $productDetail->getProduct();

                                                if($lineItemProduct != null)
                                                {
                                                    echo("Record ProductDetails LineItemProduct ProductCode: " . $lineItemProduct->getProductCode() . "\n");

                                                    echo("Record ProductDetails LineItemProduct Currency: " . $lineItemProduct->getCurrency() . "\n");

                                                    echo("Record ProductDetails LineItemProduct Name: " . $lineItemProduct->getName() . "\n");

                                                    echo("Record ProductDetails LineItemProduct Id: " . $lineItemProduct->getId() . "\n");
                                                }

                                                echo("Record ProductDetails Quantity: " . $productDetail->getQuantity() . "\n");

                                                echo("Record ProductDetails Discount: " . $productDetail->getDiscount() . "\n");

                                                echo("Record ProductDetails TotalAfterDiscount: " . $productDetail->getTotalAfterDiscount() . "\n");

                                                echo("Record ProductDetails NetTotal: " . $productDetail->getNetTotal() . "\n");

                                                if($productDetail->getBook() != null)
                                                {
                                                    echo("Record ProductDetails Book: " . $productDetail->getBook() . "\n");
                                                }

                                                echo("Record ProductDetails Tax: " . $productDetail->getTax() . "\n");

                                                echo("Record ProductDetails ListPrice: " . $productDetail->getListPrice() . "\n");

                                                echo("Record ProductDetails UnitPrice: " . $productDetail->getUnitPrice() . "\n");

                                                echo("Record ProductDetails QuantityInStock: " . $productDetail->getQuantityInStock() . "\n");

                                                echo("Record ProductDetails Total: " . $productDetail->getTotal() . "\n");

                                                echo("Record ProductDetails ID: " . $productDetail->getId() . "\n");

                                                echo("Record ProductDetails ProductDescription: " . $productDetail->getProductDescription() . "\n");

                                                $lineTaxes = $productDetail->getLineTax();

                                                foreach($lineTaxes as $lineTax)
                                                {
                                                    echo("Record ProductDetails LineTax Percentage: " . $lineTax->getPercentage() . "\n");

                                                    echo("Record ProductDetails LineTax Name: " . $lineTax->getName() . "\n");

                                                    echo("Record ProductDetails LineTax Id: " . $lineTax->getId() . "\n");

                                                    echo("Record ProductDetails LineTax Value: " . $lineTax->getValue() . "\n");
                                                }
                                            }
                                        }
                                        else if($value[0] instanceof Tag)
                                        {
                                            $tagList = $value;

                                            foreach($tagList as $tag)
                                            {
                                                //Get the Name of each Tag
                                                echo("Record Tag Name: " . $tag->getName() . "\n");

                                                //Get the Id of each Tag
                                                echo("Record Tag ID: " . $tag->getId() . "\n");
                                            }
                                        }
                                        else if($value[0] instanceof PricingDetails)
                                        {
                                            $pricingDetails = $value;

                                            foreach($pricingDetails as $pricingDetail)
                                            {
                                                echo("Record PricingDetails ToRange: " . $pricingDetail->getToRange(). "\n");

                                                echo("Record PricingDetails Discount: " . $pricingDetail->getDiscount(). "\n");

                                                echo("Record PricingDetails ID: " . $pricingDetail->getId() . "\n");

                                                echo("Record PricingDetails FromRange: " . $pricingDetail->getFromRange(). "\n");
                                            }
                                        }
                                        else if($value[0] instanceof Participants)
                                        {
                                            $participants = $value;

                                            foreach($participants as $participant)
                                            {
                                                echo("RelatedRecord Participants Name: " . $participant->getName() . "\n");

                                                echo("RelatedRecord Participants Invited: " . $participant->getInvited() . "\n");

                                                echo("RelatedRecord Participants ID: " . $participant->getId() . "\n");

                                                echo("RelatedRecord Participants Type: " . $participant->getType() . "\n");

                                                echo("RelatedRecord Participants Participant: " . $participant->getParticipant() . "\n");

                                                echo("RelatedRecord Participants Status: " . $participant->getStatus() . "\n");
                                            }
                                        }
                                        else if($value[0] instanceof $recordClass)
                                        {
                                            $recordList = $value;

                                            foreach($recordList as $record1)
                                            {
                                                //Get the details map
                                                foreach($record1->getKeyValues() as $key => $value1)
                                                {
                                                    //Get each value in the map
                                                    echo($key . " : " );

                                                    print_r($value1);

                                                    echo("\n");
                                                }
                                            }
                                        }
                                        else if($value[0] instanceof LineTax)
                                        {
                                            $lineTaxes = $value;

                                            foreach($lineTaxes as $lineTax)
                                            {
                                                echo("Record ProductDetails LineTax Percentage: " . $lineTax->getPercentage(). "\n");

                                                echo("Record ProductDetails LineTax Name: " . $lineTax->getName() . "\n");

                                                echo("Record ProductDetails LineTax Id: " . $lineTax->getId() . "\n");

                                                echo("Record ProductDetails LineTax Value: " . $lineTax->getValue(). "\n");
                                            }
                                        }
                                        else if($value[0] instanceof Comment)
                                        {
                                            $comments = $value;

                                            foreach($comments as $comment)
                                            {
                                                echo("Record Comment CommentedBy: " . $comment->getCommentedBy() . "\n");

                                                echo("Record Comment CommentedTime: ");

                                                print_r($comment->getCommentedTime());

                                                echo("\n");

                                                echo("Record Comment CommentContent: " . $comment->getCommentContent(). "\n");

                                                echo("Record Comment Id: " . $comment->getId() . "\n");
                                            }
                                        }
                                        else if($value[0] instanceof Attachment)
                                        {
                                            $attachments = $value;

                                            foreach ($attachments as $attachment)
                                            {
                                                //Get the owner User instance of each attachment
                                                $owner = $attachment->getOwner();

                                                //Check if owner is not null
                                                if($owner != null)
                                                {
                                                    //Get the Name of the Owner
                                                    echo("Record Attachment Owner User-Name: " . $owner->getName() . "\n");

                                                    //Get the ID of the Owner
                                                    echo("Record Attachment Owner User-ID: " . $owner->getId() . "\n");

                                                    //Get the Email of the Owner
                                                    echo("Record Attachment Owner User-Email: " . $owner->getEmail() . "\n");
                                                }

                                                //Get the modified time of each attachment
                                                echo("Record Attachment Modified Time: ");

                                                print_r($attachment->getModifiedTime());

                                                echo("\n");

                                                //Get the name of the File
                                                echo("Record Attachment File Name: " . $attachment->getFileName() . "\n");

                                                //Get the created time of each attachment
                                                echo("Record Attachment Created Time: " );

                                                print_r($attachment->getCreatedTime());

                                                echo("\n");

                                                //Get the Attachment file size
                                                echo("Record Attachment File Size: " . $attachment->getSize() . "\n");

                                                //Get the parentId Record instance of each attachment
                                                $parentId = $attachment->getParentId();

                                                //Check if parentId is not null
                                                if($parentId != null)
                                                {
                                                    //Get the parent record Name of each attachment
                                                    echo("Record Attachment parent record Name: " . $parentId->getKeyValue("name") . "\n");

                                                    //Get the parent record ID of each attachment
                                                    echo("Record Attachment parent record ID: " . $parentId->getId() . "\n");
                                                }

                                                //Get the attachment is Editable
                                                echo("Record Attachment is Editable: " . $attachment->getEditable() . "\n");

                                                //Get the file ID of each attachment
                                                echo("Record Attachment File ID: " . $attachment->getFileId() . "\n");

                                                //Get the type of each attachment
                                                echo("Record Attachment File Type: " . $attachment->getType() . "\n");

                                                //Get the seModule of each attachment
                                                echo("Record Attachment seModule: " . $attachment->getSeModule() . "\n");

                                                //Get the modifiedBy User instance of each attachment
                                                $modifiedBy = $attachment->getModifiedBy();

                                                //Check if modifiedBy is not null
                                                if($modifiedBy != null)
                                                {
                                                    //Get the Name of the modifiedBy User
                                                    echo("Record Attachment Modified By User-Name: " . $modifiedBy->getName() . "\n");

                                                    //Get the ID of the modifiedBy User
                                                    echo("Record Attachment Modified By User-ID: " . $modifiedBy->getId() . "\n");

                                                    //Get the Email of the modifiedBy User
                                                    echo("Record Attachment Modified By User-Email: " . $modifiedBy->getEmail() . "\n");
                                                }

                                                //Get the state of each attachment
                                                echo("Record Attachment State: " . $attachment->getState() . "\n");

                                                //Get the ID of each attachment
                                                echo("Record Attachment ID: " . $attachment->getId() . "\n");

                                                //Get the createdBy User instance of each attachment
                                                $createdBy = $attachment->getCreatedBy();

                                                //Check if createdBy is not null
                                                if($createdBy != null)
                                                {
                                                    //Get the name of the createdBy User
                                                    echo("Record Attachment Created By User-Name: " . $createdBy->getName() . "\n");

                                                    //Get the ID of the createdBy User
                                                    echo("Record Attachment Created By User-ID: " . $createdBy->getId() . "\n");

                                                    //Get the Email of the createdBy User
                                                    echo("Record Attachment Created By User-Email: " . $createdBy->getEmail() . "\n");
                                                }

                                                //Get the linkUrl of each attachment
                                                echo("Record Attachment LinkUrl: " . $attachment->getLinkUrl() . "\n");
                                            }
                                        }
                                        else
                                        {
                                            echo($keyName . " : ");

                                            print_r($value);

                                            echo("\n");
                                        }
                                    }
                                    else if($value instanceof Layout)
                                    {
                                        $layout = $value;

                                        if($layout != null)
                                        {
                                            echo("Record " . $keyName. " ID: " . $layout->getId() . "\n");

                                            echo("Record " . $keyName . " Name: " . $layout->getName() . "\n");
                                        }
                                    }
                                    else if($value instanceof User)
                                    {
                                        $user = $value;

                                        if($user != null)
                                        {
                                            echo("Record " . $keyName . " User-ID: " . $user->getId() . "\n");

                                            echo("Record " . $keyName . " User-Name: " . $user->getName() . "\n");

                                            echo("Record " . $keyName . " User-Email: " . $user->getEmail() . "\n");
                                        }
                                    }
                                    else if($value instanceof $recordClass)
                                    {
                                        $recordValue = $value;

                                        echo("Record " . $keyName . " ID: " . $recordValue->getId() . "\n");

                                        echo("Record " . $keyName . " Name: " . $recordValue->getKeyValue("name") . "\n");
                                    }
                                    else if($value instanceof Choice)
                                    {
                                        $choiceValue = $value;

                                        echo("Record " . $keyName . " : " . $choiceValue->getValue() . "\n");
                                    }
                                    else if($value instanceof RemindAt)
                                    {
                                        echo($keyName . ": " . $value->getAlarm() . "\n");
                                    }
                                    else if($value instanceof RecurringActivity)
                                    {
                                        echo($keyName . " : RRULE" . ": " . $value->getRrule() . "\n");
                                    }
                                    else if($value instanceof Consent)
                                    {
                                        $consent = $value;

                                        echo("Record Consent ID: " . $consent->getId());

                                        //Get the Owner User instance of each attachment
                                        $owner = $consent->getOwner();

                                        //Check if owner is not null
                                        if($owner != null)
                                        {
                                            //Get the name of the owner User
                                            echo("Record Consent Owner Name: " . $owner->getName());

                                            //Get the ID of the owner User
                                            echo("Record Consent Owner ID: " . $owner->getId());

                                            //Get the Email of the owner User
                                            echo("Record Consent Owner Email: " . $owner->getEmail());
                                        }

                                        $consentCreatedBy = $consent->getCreatedBy();

                                        //Check if createdBy is not null
                                        if($consentCreatedBy != null)
                                        {
                                            //Get the name of the CreatedBy User
                                            echo("Record Consent CreatedBy Name: " . $consentCreatedBy->getName());

                                            //Get the ID of the CreatedBy User
                                            echo("Record Consent CreatedBy ID: " . $consentCreatedBy->getId());

                                            //Get the Email of the CreatedBy User
                                            echo("Record Consent CreatedBy Email: " . $consentCreatedBy->getEmail());
                                        }

                                        $consentModifiedBy = $consent->getModifiedBy();

                                        //Check if createdBy is not null
                                        if($consentModifiedBy != null)
                                        {
                                            //Get the name of the ModifiedBy User
                                            echo("Record Consent ModifiedBy Name: " . $consentModifiedBy->getName());

                                            //Get the ID of the ModifiedBy User
                                            echo("Record Consent ModifiedBy ID: " . $consentModifiedBy->getId());

                                            //Get the Email of the ModifiedBy User
                                            echo("Record Consent ModifiedBy Email: " . $consentModifiedBy->getEmail());
                                        }

                                        echo("Record Consent CreatedTime: " . $consent->getCreatedTime());

                                        echo("Record Consent ModifiedTime: " . $consent->getModifiedTime());

                                        echo("Record Consent ContactThroughEmail: " . $consent->getContactThroughEmail());

                                        echo("Record Consent ContactThroughSocial: " . $consent->getContactThroughSocial());

                                        echo("Record Consent ContactThroughSurvey: " . $consent->getContactThroughSurvey());

                                        echo("Record Consent ContactThroughPhone: " . $consent->getContactThroughPhone());

                                        echo("Record Consent MailSentTime: " . $consent->getMailSentTime().toString());

                                        echo("Record Consent ConsentDate: " . $consent->getConsentDate().toString());

                                        echo("Record Consent ConsentRemarks: " . $consent->getConsentRemarks());

                                        echo("Record Consent ConsentThrough: " . $consent->getConsentThrough());

                                        echo("Record Consent DataProcessingBasis: " . $consent->getDataProcessingBasis());

                                        //To get custom values
                                        echo("Record Consent Lawful Reason: " . $consent->getKeyValue("Lawful_Reason"));
                                    }
                                    else
                                    {
                                        //Get each value in the map
                                        echo($keyName . " : ");

                                        print_r($value);

                                        echo("\n");
                                    }
                                }
                            }
                        }
                    }
                }
                else if($responseHandler instanceof FileBodyWrapper)
                {
                    //Get object from response
                    $fileBodyWrapper = $responseHandler;

                    //Get StreamWrapper instance from the returned FileBodyWrapper instance
                    $streamWrapper = $fileBodyWrapper->getFile();

                    //Create a file instance with the absolute_file_path
                    $fp = fopen($destinationFolder."/".$streamWrapper->getName(), "w");

                    //Get stream from the response
                    $stream = $streamWrapper->getStream();

                    fputs($fp, $stream);

                    fclose($fp);
                }
                //Check if the request returned an exception
                else if($responseHandler instanceof APIException)
                {
                    //Get the received APIException instance
                    $exception = $responseHandler;

                    //Get the Status
                    echo("Status: " . $exception->getStatus()->getValue() . "\n");

                    //Get the Code
                    echo("Code: " . $exception->getCode()->getValue() . "\n");

                    echo("Details: " );

                    //Get the details map
                    foreach($exception->getDetails() as $key => $value)
                    {
                        //Get each value in the map
                        echo($key . " : " . $value . "\n");
                    }

                    //Get the Message
                    echo("Message: " . $exception->getMessage()->getValue() . "\n");
                }
            }
            else
            {
                print_r($response);
            }
        }
    }

    public static function createRecord(string $moduleAPIName)
    {

        //Get instance of RecordOperations Class that takes moduleAPIName as parameter
        $recordOperations = new RecordOperations();

        //Get instance of BodyWrapper Class that will contain the request body
        $bodyWrapper = new BodyWrapper();

        //List of Record instances
        $records = array();

        $recordClass = 'com\zoho\crm\api\record\Record';

        /**
         * @var $record1 Record
         */
        //Get instance of Record Class
        //$record1 = new $recordClass();
        $record1 = new Record();

        /*
         * Call addFieldValue method that takes two arguments
         * 1 -> Call Field "." and choose the module from the displayed list and press "." and choose the field name from the displayed list.
         * 2 -> Value
         */
        $field = new Field("");

        // $record1->addFieldValue(Leads::City(), "City");

        $record1->addFieldValue(Leads::LastName(), "FROm PHP");

        // $record1->addFieldValue(Leads::FirstName(), "First Name");

        // $record1->addFieldValue(Leads::Company(), "KKRNP");

        // $record1->addFieldValue(Vendors::VendorName(), "Vendor Name");

        // $record1->addFieldValue(Deals::Stage(), new Choice("Clo"));

        // $record1->addFieldValue(Deals::DealName(), "deal_name");

        // $record1->addFieldValue(Deals::Description(), "deals description");

        // $record1->addFieldValue(Deals::ClosingDate(), new \DateTime("2021-06-02"));

        // $record1->addFieldValue(Deals::Amount(), 50.7);

        // $record1->addFieldValue(Campaigns::CampaignName(), "Campaign_Name");

        // $record1->addFieldValue(Solutions::SolutionTitle(), "Solution_Title");

        $record1->addFieldValue(Accounts::AccountName(), "Account_Name");

        // $record1->addFieldValue(Cases::CaseOrigin(), new Choice("AutomatedSDK"));

        // $record1->addFieldValue(Cases::Status(), new Choice("AutomatedSDK"));

        /*
         * Call addKeyValue method that takes two arguments
         * 1 -> A string that is the Field's API Name
         * 2 -> Value
         */
        // $record1->addKeyValue("Custom_field", "Value");

        // $record1->addKeyValue("Custom_field_2", "value");

        $record1->addKeyValue("Date_1", new \DateTime('2021-03-08'));

        $record1->addKeyValue("Date_Time_2", date_create("2020-06-02T11:03:06+05:30")->setTimezone(new \DateTimeZone(date_default_timezone_get())));

        // $record1->addKeyValue("Subject", "From PHP");

        // $taxName = array(new Choice("Vat"), new Choice("Sales Tax"));

        // $record1->addKeyValue("Tax", $taxName);

        // $record1->addKeyValue("Product_Name", "AutomatedSDK");

        // $fileDetails = array();

        // $fileDetail1 = new FileDetails();

        // $fileDetail1->setFileId("ae9c7cefa418aec1d6a5cc2d9ab35c32a6d84fd0653c0fe3eb5d30f3c0dee629");

        // array_push($fileDetails, $fileDetail1);

        // $fileDetail2 = new FileDetails();

        // $fileDetail2->setFileId("ae9c7cefa418aec1d6a5cc2d9ab35c32cf8c21acc735a439b1e84e92ec8454d7");

        // array_push($fileDetails, $fileDetail2);

        // $fileDetail3 = new FileDetails();

        // $fileDetail3->setFileId("ae9c7cefa418aec1d6a5cc2d9ab35c3207c8e1a4448a63b609f1ba7bd4aee6eb");

        // array_push($fileDetails, $fileDetail3);

        // $record1->addKeyValue("File_Upload", $fileDetails);

        // /** Following methods are being used only by Inventory modules */

        // $vendorName = new $recordClass();

        // $record1->addFieldValue(Vendors::id(), "3477061000007247001");

        // $record1->addFieldValue(Purchase_Orders::VendorName(), $vendorName);

        // $dealName = new $recordClass();

        // $dealName->addFieldValue(Deals::id(), "3477061000004995070");

        // $record1->addFieldValue(Sales_Orders::DealName(), $dealName);

        // $contactName = new $recordClass();

        // $contactName->addFieldValue(Contacts::id(), "3477061000004977055");

        // $record1->addFieldValue(Purchase_Orders::ContactName(), $contactName);

        // $accountName = new $recordClass();

        // $accountName->addKeyValue("name", "automatedAccount");

        // $record1->addFieldValue(Quotes::AccountName(), $accountName);

        // $record1->addKeyValue("Discount", 10.5);

        // $inventoryLineItemList = array();

        // $inventoryLineItem = new InventoryLineItems();

        // $lineItemProduct = new LineItemProduct();

        // $lineItemProduct->setId("3477061000005356009");

        // $inventoryLineItem->setProduct($lineItemProduct);

        // $inventoryLineItem->setQuantity(1.5);

        // $inventoryLineItem->setProductDescription("productDescription");

        // $inventoryLineItem->setListPrice(10.0);

        // $inventoryLineItem->setDiscount("5.0");

        // $inventoryLineItem->setDiscount("5.25%");

        // $productLineTaxes = array();

        // $productLineTax = new LineTax();

        // $productLineTax->setName("Sales Tax");

        // $productLineTax->setPercentage(20.0);

        // array_push($productLineTaxes, $productLineTax);

        // $inventoryLineItem->setLineTax($productLineTaxes);

        // array_push($inventoryLineItemList, $inventoryLineItem);

        // $record1->addKeyValue("Product_Details", $inventoryLineItemList);

        // $lineTaxes = array();

        // $lineTax = new LineTax();

        // $lineTax->setName("Sales Tax");

        // $lineTax->setPercentage(20.0);

        // array_push($lineTaxes,$lineTax);

        // $record1->addKeyValue('$line_tax', $lineTaxes);

        /** End Inventory **/





        /** Following methods are being used only by Activity modules */

        // Tasks,Calls,Events
        $task = new Record();
        $task->addFieldValue(Tasks::Subject(), "Subject Test Task");
        $task->addFieldValue(Tasks::Description(), "Test Task");

        $task->addKeyValue("Currency",new Choice("INR"));

        //$remindAt = new RemindAt();

        //$remindAt->setAlarm("FREQ=NONE;ACTION=EMAILANDPOPUP;TRIGGER=DATE-TIME:2020-07-03T12:30:00.05:30");

        //$task->addFieldValue(Tasks::RemindAt(), $remindAt);

        $whoId = new $recordClass();

        // $whoId->setId("3477061000004977055");

        // $record1->addFieldValue(Tasks::WhoId(), $whoId);

        $task->addFieldValue(Tasks::Status(),new Choice("Waiting for input"));

        $task->addFieldValue(Tasks::DueDate(), new \DateTime('2021-03-08'));

        $task->addFieldValue(Tasks::Priority(),new Choice("High"));

        $task->addKeyValue('$se_module', "Leads");

        $whatId = new $recordClass();

        $whatId->setId("4806354000000352001");

        $task->addFieldValue(Tasks::WhatId(), $whatId);



        /** Recurring Activity can be provided in any activity module*/

        // $recurringActivity = new RecurringActivity();

        // $recurringActivity->setRrule("FREQ=DAILY;INTERVAL=10;UNTIL=2020-08-14;DTSTART=2020-07-03");

        // $record1->addFieldValue(Events::RecurringActivity(), $recurringActivity);

        // Events
        // $record1->addFieldValue(Events::Description(), "Test Events");

        $startdatetime = date_create("2020-06-02T11:03:06+05:30")->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        $record1->addFieldValue(Events::StartDateTime(), $startdatetime);

        // $participants = array();

        // $participant1 = new Participants();

        // $participant1->setParticipant("raja@gmail.com");

        // $participant1->setType("email");

        // $participant1->setId("3477061000005902017");

        // array_push($participants, $participant1);

        // $participant2 = new Participants();

        // $participant2->addKeyValue("participant", "3477061000005844006");

        // $participant2->addKeyValue("type", "lead");

        // array_push($participants, $participant2);

        // $record1->addFieldValue(Events::Participants(), $participants);

        // $record1->addKeyValue('$send_notification', true);

        $record1->addFieldValue(Events::EventTitle(), "From PHP");

        $enddatetime = date_create("2020-07-02T11:03:06+05:30")->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        $record1->addFieldValue(Events::EndDateTime(), $enddatetime);

        // $remindAt = date_create("2020-06-02T11:03:06+05:30")->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        // $record1->addFieldValue(Events::RemindAt(), $remindAt);

        // $record1->addFieldValue(Events::CheckInStatus(), "PLANNED");

        // $remindAt = new RemindAt();

        // $remindAt->setAlarm("FREQ=NONE;ACTION=EMAILANDPOPUP;TRIGGER=DATE-TIME:2020-07-23T12:30:00+05:30");

        // $record1->addFieldValue(Tasks::RemindAt(), $remindAt);

        // $record1->addKeyValue('$se_module', "Leads");

        // $whatId = new $recordClass();

        // $whatId->setId("3477061000004381002");

        // $record1->addFieldValue(Events::WhatId(), $whatId);

        // $record1->addFieldValue(Tasks::WhatId(), $whatId);

        // $record1->addFieldValue(Calls::CallType(), new Choice("Outbound"));

        // $record1->addFieldValue(Calls::CallStartTime(), date_create("2020-07-02T11:03:06+05:30")->setTimezone(new \DateTimeZone(date_default_timezone_get())));

        /** End Activity **/



        /** Following methods are being used only by Price_Books modules */

        // $pricingDetails = array();

        // $pricingDetail1 = new PricingDetails();

        // $pricingDetail1->setFromRange(1.0);

        // $pricingDetail1->setToRange(5.0);

        // $pricingDetail1->setDiscount(2.0);

        // array_push($pricingDetails, $pricingDetail1);

        // $pricingDetail2 = new PricingDetails();

        // $pricingDetail2->addKeyValue("from_range", 6.0);

        // $pricingDetail2->addKeyValue("to_range", 11.0);

        // $pricingDetail2->addKeyValue("discount", 3.0);

        // array_push($pricingDetails, $pricingDetail2);

        // $record1->addFieldValue(Price_Books::PricingDetails(), $pricingDetails);

        // $record1->addKeyValue("Email", "raja.k123@zoho.com");

        $record1->addFieldValue(Price_Books::Description(), "Price_book_TEST");

        $record1->addFieldValue(Price_Books::PriceBookName(), "book_name");

        $record1->addFieldValue(Price_Books::PricingModel(), new Choice("Flat"));

        $tagList = array();

        $tag = new Tag();

        $tag->setName("Testtask");

        array_push($tagList, $tag);

        //Set the list to Tags in Record instance
        $record1->setTag($tagList);

        //Add Record instance to the list
        // array_push($records, $record1);

        //dd($record1);

        //Set the list to Records in BodyWrapper instance


        //dump($task);exit;
        //$bodyWrapper->setData([$record1]);
        $bodyWrapper->setData([$task]);

        $trigger = array("approval", "workflow", "blueprint");

        $bodyWrapper->setTrigger($trigger);

        //bodyWrapper.setLarId("3477061000000087515");

        //dd($bodyWrapper);

        //Call createRecords method that takes BodyWrapper instance as parameter.
        $moduleAPIName = 'Tasks';
        $response = $recordOperations->createRecords($moduleAPIName,$bodyWrapper);

        dump($response);


        if($response != null)
        {
            //Get the status code from response
            echo("Status Code: " . $response->getStatusCode() . "\n");

            if($response->isExpected())
            {
                //Get object from response
                $actionHandler = $response->getObject();

                if($actionHandler instanceof ActionWrapper)
                {
                    //Get the received ActionWrapper instance
                    $actionWrapper = $actionHandler;

                    //Get the list of obtained ActionResponse instances
                    $actionResponses = $actionWrapper->getData();

                    foreach($actionResponses as $actionResponse)
                    {
                        //Check if the request is successful
                        if($actionResponse instanceof SuccessResponse)
                        {
                            //Get the received SuccessResponse instance
                            $successResponse = $actionResponse;

                            //Get the Status
                            echo("Status: " . $successResponse->getStatus()->getValue() . "\n");

                            //Get the Code
                            echo("Code: " . $successResponse->getCode()->getValue() . "\n");

                            echo("Details: " );

                            //Get the details map
                            foreach($successResponse->getDetails() as $key => $value)
                            {
                                //Get each value in the map
                                echo($key . " : ");

                                print_r($value);

                                echo("\n");
                            }

                            //Get the Message
                            echo("Message: " . $successResponse->getMessage()->getValue() . "\n");
                        }
                        //Check if the request returned an exception
                        else if($actionResponse instanceof APIException)
                        {
                            //Get the received APIException instance
                            $exception = $actionResponse;

                            //Get the Status
                            echo("Status: " . $exception->getStatus()->getValue() . "\n");

                            //Get the Code
                            echo("Code: " . $exception->getCode()->getValue() . "\n");

                            echo("Details: " );

                            //Get the details map
                            foreach($exception->getDetails() as $key => $value)
                            {
                                //Get each value in the map
                                echo($key . " : " . $value . "\n");
                            }

                            //Get the Message
                            echo("Message: " . $exception->getMessage()->getValue() . "\n");
                        }
                    }
                }
                //Check if the request returned an exception
                else if($actionHandler instanceof APIException)
                {
                    //Get the received APIException instance
                    $exception = $actionHandler;

                    //Get the Status
                    echo("Status: " . $exception->getStatus()->getValue() . "\n");

                    //Get the Code
                    echo("Code: " . $exception->getCode()->getValue() . "\n");

                    echo("Details: " );

                    //Get the details map
                    foreach($exception->getDetails() as $key => $value)
                    {
                        //Get each value in the map
                        echo($key . " : " . $value . "\n");
                    }

                    //Get the Message
                    echo("Message: " . $exception->getMessage()->getValue() . "\n");
                }
            }
            else
            {
                print_r($response);
            }
        }
    }

    public static function createRecords(string $moduleAPIName)
    {
        //API Name of the module to create records
        //$moduleAPIName = "Leads";

        //Get instance of RecordOperations Class that takes moduleAPIName as parameter
        $recordOperations = new RecordOperations();

        //Get instance of BodyWrapper Class that will contain the request body
        $bodyWrapper = new BodyWrapper();

        //List of Record instances
        $records = array();

        $recordClass = 'com\zoho\crm\api\record\Record';

        /**
         * @var $record1 Record
         */
        //Get instance of Record Class
        //$record1 = new $recordClass();
          $record1 = new Record();

        /*
         * Call addFieldValue method that takes two arguments
         * 1 -> Call Field "." and choose the module from the displayed list and press "." and choose the field name from the displayed list.
         * 2 -> Value
         */
        $field = new Field("");

        // $record1->addFieldValue(Leads::City(), "City");

        $record1->addFieldValue(Leads::LastName(), "FROm PHP");

        // $record1->addFieldValue(Leads::FirstName(), "First Name");

        // $record1->addFieldValue(Leads::Company(), "KKRNP");

        // $record1->addFieldValue(Vendors::VendorName(), "Vendor Name");

        // $record1->addFieldValue(Deals::Stage(), new Choice("Clo"));

        // $record1->addFieldValue(Deals::DealName(), "deal_name");

        // $record1->addFieldValue(Deals::Description(), "deals description");

        // $record1->addFieldValue(Deals::ClosingDate(), new \DateTime("2021-06-02"));

        // $record1->addFieldValue(Deals::Amount(), 50.7);

        // $record1->addFieldValue(Campaigns::CampaignName(), "Campaign_Name");

        // $record1->addFieldValue(Solutions::SolutionTitle(), "Solution_Title");

        $record1->addFieldValue(Accounts::AccountName(), "Account_Name");

        // $record1->addFieldValue(Cases::CaseOrigin(), new Choice("AutomatedSDK"));

        // $record1->addFieldValue(Cases::Status(), new Choice("AutomatedSDK"));

        /*
         * Call addKeyValue method that takes two arguments
         * 1 -> A string that is the Field's API Name
         * 2 -> Value
         */
        // $record1->addKeyValue("Custom_field", "Value");

        // $record1->addKeyValue("Custom_field_2", "value");

        $record1->addKeyValue("Date_1", new \DateTime('2021-03-08'));

        $record1->addKeyValue("Date_Time_2", date_create("2020-06-02T11:03:06+05:30")->setTimezone(new \DateTimeZone(date_default_timezone_get())));

        // $record1->addKeyValue("Subject", "From PHP");

        // $taxName = array(new Choice("Vat"), new Choice("Sales Tax"));

        // $record1->addKeyValue("Tax", $taxName);

        // $record1->addKeyValue("Product_Name", "AutomatedSDK");

        // $fileDetails = array();

        // $fileDetail1 = new FileDetails();

        // $fileDetail1->setFileId("ae9c7cefa418aec1d6a5cc2d9ab35c32a6d84fd0653c0fe3eb5d30f3c0dee629");

        // array_push($fileDetails, $fileDetail1);

        // $fileDetail2 = new FileDetails();

        // $fileDetail2->setFileId("ae9c7cefa418aec1d6a5cc2d9ab35c32cf8c21acc735a439b1e84e92ec8454d7");

        // array_push($fileDetails, $fileDetail2);

        // $fileDetail3 = new FileDetails();

        // $fileDetail3->setFileId("ae9c7cefa418aec1d6a5cc2d9ab35c3207c8e1a4448a63b609f1ba7bd4aee6eb");

        // array_push($fileDetails, $fileDetail3);

        // $record1->addKeyValue("File_Upload", $fileDetails);

        // /** Following methods are being used only by Inventory modules */

        // $vendorName = new $recordClass();

        // $record1->addFieldValue(Vendors::id(), "3477061000007247001");

        // $record1->addFieldValue(Purchase_Orders::VendorName(), $vendorName);

        // $dealName = new $recordClass();

        // $dealName->addFieldValue(Deals::id(), "3477061000004995070");

        // $record1->addFieldValue(Sales_Orders::DealName(), $dealName);

        // $contactName = new $recordClass();

        // $contactName->addFieldValue(Contacts::id(), "3477061000004977055");

        // $record1->addFieldValue(Purchase_Orders::ContactName(), $contactName);

        // $accountName = new $recordClass();

        // $accountName->addKeyValue("name", "automatedAccount");

        // $record1->addFieldValue(Quotes::AccountName(), $accountName);

        // $record1->addKeyValue("Discount", 10.5);

        // $inventoryLineItemList = array();

        // $inventoryLineItem = new InventoryLineItems();

        // $lineItemProduct = new LineItemProduct();

        // $lineItemProduct->setId("3477061000005356009");

        // $inventoryLineItem->setProduct($lineItemProduct);

        // $inventoryLineItem->setQuantity(1.5);

        // $inventoryLineItem->setProductDescription("productDescription");

        // $inventoryLineItem->setListPrice(10.0);

        // $inventoryLineItem->setDiscount("5.0");

        // $inventoryLineItem->setDiscount("5.25%");

        // $productLineTaxes = array();

        // $productLineTax = new LineTax();

        // $productLineTax->setName("Sales Tax");

        // $productLineTax->setPercentage(20.0);

        // array_push($productLineTaxes, $productLineTax);

        // $inventoryLineItem->setLineTax($productLineTaxes);

        // array_push($inventoryLineItemList, $inventoryLineItem);

        // $record1->addKeyValue("Product_Details", $inventoryLineItemList);

        // $lineTaxes = array();

        // $lineTax = new LineTax();

        // $lineTax->setName("Sales Tax");

        // $lineTax->setPercentage(20.0);

        // array_push($lineTaxes,$lineTax);

        // $record1->addKeyValue('$line_tax', $lineTaxes);

        /** End Inventory **/





        /** Following methods are being used only by Activity modules */

        // Tasks,Calls,Events
          $task = new Record();
        $task->addFieldValue(Tasks::Subject(), "Subject Test Task");
          $task->addFieldValue(Tasks::Description(), "Test Task");

          $task->addKeyValue("Currency",new Choice("INR"));

          //$remindAt = new RemindAt();

          //$remindAt->setAlarm("FREQ=NONE;ACTION=EMAILANDPOPUP;TRIGGER=DATE-TIME:2020-07-03T12:30:00.05:30");

          //$task->addFieldValue(Tasks::RemindAt(), $remindAt);

          $whoId = new $recordClass();

        // $whoId->setId("3477061000004977055");

        // $record1->addFieldValue(Tasks::WhoId(), $whoId);

           $task->addFieldValue(Tasks::Status(),new Choice("Waiting for input"));

           $task->addFieldValue(Tasks::DueDate(), new \DateTime('2021-03-08'));

           $task->addFieldValue(Tasks::Priority(),new Choice("High"));

           $task->addKeyValue('$se_module', "Leads");

           $whatId = new $recordClass();

           $whatId->setId("4806354000000352001");

           $task->addFieldValue(Tasks::WhatId(), $whatId);



        /** Recurring Activity can be provided in any activity module*/

        // $recurringActivity = new RecurringActivity();

        // $recurringActivity->setRrule("FREQ=DAILY;INTERVAL=10;UNTIL=2020-08-14;DTSTART=2020-07-03");

        // $record1->addFieldValue(Events::RecurringActivity(), $recurringActivity);

        // Events
        // $record1->addFieldValue(Events::Description(), "Test Events");

        $startdatetime = date_create("2020-06-02T11:03:06+05:30")->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        $record1->addFieldValue(Events::StartDateTime(), $startdatetime);

        // $participants = array();

        // $participant1 = new Participants();

        // $participant1->setParticipant("raja@gmail.com");

        // $participant1->setType("email");

        // $participant1->setId("3477061000005902017");

        // array_push($participants, $participant1);

        // $participant2 = new Participants();

        // $participant2->addKeyValue("participant", "3477061000005844006");

        // $participant2->addKeyValue("type", "lead");

        // array_push($participants, $participant2);

        // $record1->addFieldValue(Events::Participants(), $participants);

        // $record1->addKeyValue('$send_notification', true);

        $record1->addFieldValue(Events::EventTitle(), "From PHP");

        $enddatetime = date_create("2020-07-02T11:03:06+05:30")->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        $record1->addFieldValue(Events::EndDateTime(), $enddatetime);

        // $remindAt = date_create("2020-06-02T11:03:06+05:30")->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        // $record1->addFieldValue(Events::RemindAt(), $remindAt);

        // $record1->addFieldValue(Events::CheckInStatus(), "PLANNED");

        // $remindAt = new RemindAt();

        // $remindAt->setAlarm("FREQ=NONE;ACTION=EMAILANDPOPUP;TRIGGER=DATE-TIME:2020-07-23T12:30:00+05:30");

        // $record1->addFieldValue(Tasks::RemindAt(), $remindAt);

        // $record1->addKeyValue('$se_module', "Leads");

        // $whatId = new $recordClass();

        // $whatId->setId("3477061000004381002");

        // $record1->addFieldValue(Events::WhatId(), $whatId);

        // $record1->addFieldValue(Tasks::WhatId(), $whatId);

        // $record1->addFieldValue(Calls::CallType(), new Choice("Outbound"));

        // $record1->addFieldValue(Calls::CallStartTime(), date_create("2020-07-02T11:03:06+05:30")->setTimezone(new \DateTimeZone(date_default_timezone_get())));

        /** End Activity **/



        /** Following methods are being used only by Price_Books modules */

        // $pricingDetails = array();

        // $pricingDetail1 = new PricingDetails();

        // $pricingDetail1->setFromRange(1.0);

        // $pricingDetail1->setToRange(5.0);

        // $pricingDetail1->setDiscount(2.0);

        // array_push($pricingDetails, $pricingDetail1);

        // $pricingDetail2 = new PricingDetails();

        // $pricingDetail2->addKeyValue("from_range", 6.0);

        // $pricingDetail2->addKeyValue("to_range", 11.0);

        // $pricingDetail2->addKeyValue("discount", 3.0);

        // array_push($pricingDetails, $pricingDetail2);

        // $record1->addFieldValue(Price_Books::PricingDetails(), $pricingDetails);

        // $record1->addKeyValue("Email", "raja.k123@zoho.com");

         $record1->addFieldValue(Price_Books::Description(), "Price_book_TEST");

         $record1->addFieldValue(Price_Books::PriceBookName(), "book_name");

         $record1->addFieldValue(Price_Books::PricingModel(), new Choice("Flat"));

        $tagList = array();

        $tag = new Tag();

        $tag->setName("Testtask");

        array_push($tagList, $tag);

        //Set the list to Tags in Record instance
        $record1->setTag($tagList);

        //Add Record instance to the list
        // array_push($records, $record1);

        //dd($record1);

        //Set the list to Records in BodyWrapper instance


        //dump($task);exit;
        //$bodyWrapper->setData([$record1]);
        $bodyWrapper->setData([$task]);

        $trigger = array("approval", "workflow", "blueprint");

        $bodyWrapper->setTrigger($trigger);

        //bodyWrapper.setLarId("3477061000000087515");

        //dd($bodyWrapper);

        //Call createRecords method that takes BodyWrapper instance as parameter.
        $moduleAPIName = 'Tasks';
        //$response = $recordOperations->createRecords($moduleAPIName,$bodyWrapper);

//dump($response);
        $response = null;

        if($response != null)
        {
            //Get the status code from response
            echo("Status Code: " . $response->getStatusCode() . "\n");

            if($response->isExpected())
            {
                //Get object from response
                $actionHandler = $response->getObject();

                if($actionHandler instanceof ActionWrapper)
                {
                    //Get the received ActionWrapper instance
                    $actionWrapper = $actionHandler;

                    //Get the list of obtained ActionResponse instances
                    $actionResponses = $actionWrapper->getData();

                    foreach($actionResponses as $actionResponse)
                    {
                        //Check if the request is successful
                        if($actionResponse instanceof SuccessResponse)
                        {
                            //Get the received SuccessResponse instance
                            $successResponse = $actionResponse;

                            //Get the Status
                            echo("Status: " . $successResponse->getStatus()->getValue() . "\n");

                            //Get the Code
                            echo("Code: " . $successResponse->getCode()->getValue() . "\n");

                            echo("Details: " );

                            //Get the details map
                            foreach($successResponse->getDetails() as $key => $value)
                            {
                                //Get each value in the map
                                echo($key . " : ");

                                print_r($value);

                                echo("\n");
                            }

                            //Get the Message
                            echo("Message: " . $successResponse->getMessage()->getValue() . "\n");
                        }
                        //Check if the request returned an exception
                        else if($actionResponse instanceof APIException)
                        {
                            //Get the received APIException instance
                            $exception = $actionResponse;

                            //Get the Status
                            echo("Status: " . $exception->getStatus()->getValue() . "\n");

                            //Get the Code
                            echo("Code: " . $exception->getCode()->getValue() . "\n");

                            echo("Details: " );

                            //Get the details map
                            foreach($exception->getDetails() as $key => $value)
                            {
                                //Get each value in the map
                                echo($key . " : " . $value . "\n");
                            }

                            //Get the Message
                            echo("Message: " . $exception->getMessage()->getValue() . "\n");
                        }
                    }
                }
                //Check if the request returned an exception
                else if($actionHandler instanceof APIException)
                {
                    //Get the received APIException instance
                    $exception = $actionHandler;

                    //Get the Status
                    echo("Status: " . $exception->getStatus()->getValue() . "\n");

                    //Get the Code
                    echo("Code: " . $exception->getCode()->getValue() . "\n");

                    echo("Details: " );

                    //Get the details map
                    foreach($exception->getDetails() as $key => $value)
                    {
                        //Get each value in the map
                        echo($key . " : " . $value . "\n");
                    }

                    //Get the Message
                    echo("Message: " . $exception->getMessage()->getValue() . "\n");
                }
            }
            else
            {
                print_r($response);
            }
        }
    }

}