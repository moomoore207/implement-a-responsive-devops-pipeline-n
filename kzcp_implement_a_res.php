<?php
require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
use Monolog\Logger;

class DevOpsNotifier {
    private $client;
    private $logger;

    public function __construct() {
        $this->client = new Client();
        $this->logger = new Logger('devops-notifier');
    }

    public function notify($pipelineStatus, $pipelineUrl, $slackWebhookUrl) {
        if ($pipelineStatus === 'success') {
            $message = "Pipeline succeeded: $pipelineUrl";
        } else {
            $message = "Pipeline failed: $pipelineUrl";
        }

        $this->logger->info($message);

        $data = [
            'text' => $message,
            'username' => 'DevOps Notifier',
            'icon_emoji' => $pipelineStatus === 'success' ? ':white_check_mark:' : ':x:'
        ];

        $response = $this->client->post($slackWebhookUrl, ['json' => $data]);

        if ($response->getStatusCode() !== 200) {
            $this->logger->error("Error sending notification to Slack: " . $response->getBody()->getContents());
        }
    }
}

function getPipelineStatus($pipelineUrl) {
    $client = new Client();
    $response = $client->get($pipelineUrl);

    if ($response->getStatusCode() !== 200) {
        throw new Exception("Error fetching pipeline status: " . $response->getBody()->getContents());
    }

    $body = json_decode($response->getBody()->getContents(), true);

    return $body['status'];
}

$notifier = new DevOpsNotifier();

$pipelineUrl = 'https://your-pipeline-url.com';
$slackWebhookUrl = 'https://your-slack-webhook-url.com';

try {
    $pipelineStatus = getPipelineStatus($pipelineUrl);
    $notifier->notify($pipelineStatus, $pipelineUrl, $slackWebhookUrl);
} catch (Exception $e) {
    $notifier->logger->error($e->getMessage());
}