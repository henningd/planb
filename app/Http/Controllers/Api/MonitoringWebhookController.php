<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\MonitoringAlert;
use App\Services\Monitoring\AlertProcessor;
use App\Services\Monitoring\PrometheusPayload;
use App\Services\Monitoring\ZabbixPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringWebhookController extends Controller
{
    public function __construct(private readonly AlertProcessor $processor) {}

    public function zabbix(Request $request): JsonResponse
    {
        $token = $this->token($request);
        $payload = $request->json()->all();

        $alerts = ZabbixPayload::normalize(is_array($payload) ? $payload : []);
        $records = [];
        foreach ($alerts as $alert) {
            $records[] = $this->processor->process($alert, $token);
        }

        return $this->respond($records);
    }

    public function prometheus(Request $request): JsonResponse
    {
        $token = $this->token($request);
        $payload = $request->json()->all();

        $alerts = PrometheusPayload::normalize(is_array($payload) ? $payload : []);
        $records = [];
        foreach ($alerts as $alert) {
            $records[] = $this->processor->process($alert, $token);
        }

        return $this->respond($records);
    }

    private function token(Request $request): ApiToken
    {
        $token = $request->attributes->get('api_token');
        abort_unless($token instanceof ApiToken, 401);

        return $token;
    }

    /**
     * @param  list<MonitoringAlert>  $records
     */
    private function respond(array $records): JsonResponse
    {
        return response()->json([
            'received' => count($records),
            'alerts' => array_map(fn ($r) => [
                'id' => $r->id,
                'handling' => $r->handling,
                'system_id' => $r->system_id,
                'incident_report_id' => $r->incident_report_id,
            ], $records),
        ], 202);
    }
}
