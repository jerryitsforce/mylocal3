<?php
namespace Branch8\AppSettings\Model\Service;

use Branch8\AppSettings\Helper\Data;
use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class FirestoreClient
{
    public function __construct(
        protected Data $configHelper,
        protected LoggerInterface $logger
    ) {}

    private function token(): string
    {
        $sa = $this->configHelper->getFirebaseServiceAccountCredentialJson();

        if (!$sa) {
            $this->logger->error('FirestoreClient: Firestore Service Account is missing/invalid');
            throw new \RuntimeException('Firestore Service Account is missing/invalid');
        }
        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/datastore'],
            json_decode($sa, true)
        );
        $tok = $credentials->fetchAuthToken();
        if (empty($tok['access_token'])) {
            $this->logger->error('FirestoreClient: Cannot fetch Google access token');
            throw new \RuntimeException('Cannot fetch Google access token');
        }
        return $tok['access_token'];
    }

    private function http(): Client
    {
        return new Client(['base_uri' => 'https://firestore.googleapis.com/v1/', 'timeout' => 8]);
    }

    /** Encode PHP -> Firestore Value */
    private function enc(mixed $v): array
    {
        if (is_int($v))   return ['integerValue' => (string)$v];
        if (is_bool($v))  return ['booleanValue' => $v];
        if (is_float($v)) return ['doubleValue'  => $v];
        if ($v instanceof \DateTimeInterface) return ['timestampValue' => $v->format('Y-m-d\TH:i:s\Z')];
        if (is_array($v)) {
            $assoc = array_keys($v) !== range(0, count($v)-1);
            if ($assoc) {
                $fields = [];
                foreach ($v as $k => $vv) $fields[$k] = $this->enc($vv);
                return ['mapValue' => ['fields' => $fields]];
            }
            return ['arrayValue' => ['values' => array_map([$this,'enc'], $v)]];
        }
        return ['stringValue' => (string)$v];
    }

    public function upsertVersionConfig(string $env, array $payload): void
    {
        $project = $this->configHelper->getFirebaseProjectId();
        if (!$project) throw new \RuntimeException('Missing Firebase project_id');

        // server stamps
        $payload['epoch'] = time();
        $payload['updated_at'] = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        // Encode Firestore Document
        $doc = ['fields' => []];
        foreach ($payload as $k => $v) {
            $doc['fields'][$k] = $this->enc($v);
        }

        $path = sprintf(
            'projects/%s/databases/(default)/documents/app_version_config/%s',
            $project,
            strtolower($env)
        );

        $fieldPaths = array_keys($payload);
        $query = implode('&', array_map(
            fn($f) => 'updateMask.fieldPaths='.rawurlencode($f),
            $fieldPaths
        ));

        $res = $this->http()->request('PATCH', $path.'?'.$query, [
            'headers' => [
                'Authorization' => 'Bearer '.$this->token(),
                'Content-Type'  => 'application/json',
            ],
            'json' => $doc,
        ]);

        $code = $res->getStatusCode();
        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException('Firestore PATCH failed: HTTP '.$code.' '.$res->getBody());
        }
    }
}
