<?php

namespace App\Services\AI;

use App\Models\QueueTicket;
use App\Models\QueueType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QueueWaitTimePredictor
{
    protected $useAI = true; // Set true untuk pakai Gemini AI
    protected $geminiApiKey;
    
    public function __construct()
    {
        $this->geminiApiKey = config('services.gemini.api_key');
    }
    
    /**
     * Main method untuk prediksi waktu tunggu dengan AI message
     */
    public function predict($queueTypeId, $currentQueueNumber, $targetQueueNumber, $serviceDate = null)
    {
        $serviceDate = $serviceDate ?? today();
        
        // Get features untuk prediction
        $features = $this->extractFeatures($queueTypeId, $currentQueueNumber, $serviceDate);
        
        // Get queue type info
        $queueType = QueueType::with('poly')->find($queueTypeId);
        
        $remainingQueues = $targetQueueNumber - $currentQueueNumber;
        
        // Pilih method prediksi
        if ($this->useAI && $this->geminiApiKey) {
            return $this->predictWithGeminiAI($features, $queueType, $remainingQueues, $targetQueueNumber);
        }
        
        return $this->predictWithLocalModel($features, $queueType, $remainingQueues, $targetQueueNumber);
    }
    
    /**
     * Extract features dari historical data
     */
    protected function extractFeatures($queueTypeId, $currentQueueNumber, $serviceDate)
    {
        $cacheKey = "queue_features_{$queueTypeId}_" . $serviceDate->format('Y-m-d');
        
        return Cache::remember($cacheKey, 300, function () use ($queueTypeId, $serviceDate) {
            $now = now();
            $dayOfWeek = $now->dayOfWeek;
            $hour = $now->hour;
            
            // Historical average service time (last 30 days)
            $historicalAvg = $this->getHistoricalAverageServiceTime($queueTypeId, 30);
            
            // Today's performance so far
            $todayAvg = $this->getTodayAverageServiceTime($queueTypeId, $serviceDate);
            
            // Current hour performance
            $hourlyAvg = $this->getHourlyAverageServiceTime($queueTypeId, $hour);
            
            // Day of week pattern
            $dayPattern = $this->getDayOfWeekPattern($queueTypeId, $dayOfWeek);
            
            // Queue load (total tickets today)
            $queueLoad = QueueTicket::where('queue_type_id', $queueTypeId)
                ->whereDate('service_date', $serviceDate)
                ->count();
            
            // Completed and serving counts
            $completedToday = QueueTicket::where('queue_type_id', $queueTypeId)
                ->whereDate('service_date', $serviceDate)
                ->where('status', 'DONE')
                ->count();
                
            $currentlyServing = QueueTicket::where('queue_type_id', $queueTypeId)
                ->whereDate('service_date', $serviceDate)
                ->where('status', 'SERVING')
                ->count();
            
            // Waiting count
            $waitingCount = QueueTicket::where('queue_type_id', $queueTypeId)
                ->whereDate('service_date', $serviceDate)
                ->where('status', 'WAITING')
                ->count();
            
            return [
                'historical_avg' => $historicalAvg,
                'today_avg' => $todayAvg,
                'hourly_avg' => $hourlyAvg,
                'day_pattern' => $dayPattern,
                'queue_load' => $queueLoad,
                'completed_today' => $completedToday,
                'currently_serving' => $currentlyServing,
                'waiting_count' => $waitingCount,
                'day_of_week' => $dayOfWeek,
                'hour' => $hour,
                'completion_rate' => $queueLoad > 0 ? $completedToday / $queueLoad : 0,
                'day_name' => Carbon::now()->locale('id')->dayName,
                'time_period' => $this->getTimePeriod($hour),
            ];
        });
    }
    
    /**
     * Prediction dengan Gemini AI - Free tier friendly
     */
    protected function predictWithGeminiAI($features, $queueType, $remainingQueues, $targetQueueNumber)
    {
        try {
            $prompt = $this->buildGeminiPrompt($features, $queueType, $remainingQueues, $targetQueueNumber);
            
            $response = Http::timeout(15)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$this->geminiApiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topP' => 0.8,
                        'topK' => 40,
                        'maxOutputTokens' => 500,
                    ]
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $aiText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                
                if ($aiText) {
                    return $this->parseGeminiResponse($aiText, $features, $remainingQueues);
                }
            }
            
            Log::warning('Gemini API failed, using local model', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Gemini AI Prediction error: ' . $e->getMessage());
        }
        
        // Fallback to local model
        return $this->predictWithLocalModel($features, $queueType, $remainingQueues, $targetQueueNumber);
    }
    
    protected function buildGeminiPrompt($features, $queueType, $remainingQueues, $targetQueueNumber)
    {
        $polyName = $queueType->poly->name ?? 'Poli ' . $queueType->name;
        $queueCondition = $this->analyzeQueueCondition($features);
        
        return "Kamu adalah asisten AI sistem antrian rumah sakit. Analisis data antrian berikut dan berikan prediksi waktu tunggu yang akurat dengan pesan yang ramah dan informatif.

📊 DATA ANTRIAN:
Poli: {$polyName}
Nomor Antrian Target: {$targetQueueNumber}
Sisa Antrian: {$remainingQueues} orang

📈 PERFORMA HARI INI:
- Total antrian hari ini: {$features['queue_load']} pasien
- Sudah dilayani: {$features['completed_today']} pasien
- Sedang dilayani: {$features['currently_serving']} pasien
- Masih menunggu: {$features['waiting_count']} pasien
- Tingkat penyelesaian: " . round($features['completion_rate'] * 100) . "%

⏱️ DATA WAKTU PELAYANAN:
- Rata-rata historis (30 hari): {$features['historical_avg']} menit/pasien
- Rata-rata hari ini: " . ($features['today_avg'] ?? 'belum ada data') . " menit/pasien
- Rata-rata jam ini: " . ($features['hourly_avg'] ?? 'belum ada data') . " menit/pasien
- Pattern hari {$features['day_name']}: " . ($features['day_pattern'] ?? 'belum ada data') . " menit/pasien

🕐 WAKTU:
- Hari: {$features['day_name']}
- Jam: {$features['hour']}:00 ({$features['time_period']})

TUGAS:
Berikan response dalam format JSON dengan struktur berikut:
{
  \"estimated_minutes\": <angka prediksi waktu tunggu dalam menit>,
  \"confidence_level\": \"high\" atau \"medium\" atau \"low\",
  \"range_min\": <minimum waktu tunggu>,
  \"range_max\": <maximum waktu tunggu>,
  \"queue_status\": \"sepi\" atau \"normal\" atau \"ramai\" atau \"sangat_ramai\",
  \"message\": \"<pesan ramah dan informatif dalam bahasa Indonesia untuk pasien, maksimal 2-3 kalimat. Sebutkan kondisi antrian (sepi/ramai), estimasi waktu, dan saran atau motivasi>\",
  \"tips\": \"<tips singkat untuk pasien, misalnya saran datang lebih awal/nanti, atau informasi tambahan>\"
}

PANDUAN:
- Jika sisa antrian 0-5: queue_status \"sepi\"
- Jika sisa antrian 6-15: queue_status \"normal\"  
- Jika sisa antrian 16-30: queue_status \"ramai\"
- Jika sisa antrian >30: queue_status \"sangat_ramai\"
- Pertimbangkan completion rate untuk akurasi
- Jika completion rate rendah (<50%), tambahkan waktu buffer
- Pesan harus empati, positif, dan membantu
- Gunakan emoji yang sesuai dalam message
- Berikan confidence high jika ada data hari ini, medium jika hanya historical, low jika data minim

Response HANYA JSON, tidak ada teks lain.";
    }
    
    protected function parseGeminiResponse($aiText, $features, $remainingQueues)
    {
        // Clean up response - remove markdown code blocks if present
        $aiText = preg_replace('/```json\s*|\s*```/', '', $aiText);
        $aiText = trim($aiText);
        
        try {
            $parsed = json_decode($aiText, true);
            
            if ($parsed && isset($parsed['estimated_minutes'])) {
                // Validate and sanitize
                $parsed['estimated_minutes'] = max(0, (int)$parsed['estimated_minutes']);
                $parsed['range_min'] = max(0, (int)($parsed['range_min'] ?? $parsed['estimated_minutes'] * 0.8));
                $parsed['range_max'] = max($parsed['estimated_minutes'], (int)($parsed['range_max'] ?? $parsed['estimated_minutes'] * 1.2));
                $parsed['confidence_level'] = in_array($parsed['confidence_level'] ?? '', ['high', 'medium', 'low']) 
                    ? $parsed['confidence_level'] 
                    : 'medium';
                $parsed['queue_status'] = $parsed['queue_status'] ?? $this->determineQueueStatus($remainingQueues);
                $parsed['message'] = $parsed['message'] ?? 'Estimasi waktu tunggu Anda sekitar ' . $parsed['estimated_minutes'] . ' menit.';
                $parsed['tips'] = $parsed['tips'] ?? '';
                
                // Add factors for transparency
                $parsed['factors'] = [
                    'remaining_queues' => $remainingQueues,
                    'queue_load' => $features['queue_load'],
                    'completion_rate' => round($features['completion_rate'] * 100) . '%',
                    'ai_powered' => true,
                ];
                
                return $parsed;
            }
        } catch (\Exception $e) {
            Log::error('Failed to parse Gemini response: ' . $e->getMessage());
        }
        
        // Fallback
        return null;
    }
    
    /**
     * Prediction dengan local ML model (weighted average dengan pattern recognition)
     */
    protected function predictWithLocalModel($features, $queueType, $remainingQueues, $targetQueueNumber)
    {
        if ($remainingQueues <= 0) {
            return [
                'estimated_minutes' => 0,
                'confidence_level' => 'high',
                'range_min' => 0,
                'range_max' => 0,
                'queue_status' => 'sepi',
                'message' => '🎉 Antrian Anda akan segera dipanggil!',
                'tips' => 'Mohon bersiap dan pastikan Anda berada di area tunggu.',
            ];
        }
        
        // Weighted average of different time estimates
        $weights = [
            'today' => 0.4,
            'hourly' => 0.25,
            'historical' => 0.2,
            'day_pattern' => 0.15,
        ];
        
        $todayEstimate = $features['today_avg'] ?? $features['historical_avg'];
        $hourlyEstimate = $features['hourly_avg'] ?? $todayEstimate;
        $historicalEstimate = $features['historical_avg'];
        $dayEstimate = $features['day_pattern'] ?? $historicalEstimate;
        
        $baseMinutes = (
            $todayEstimate * $weights['today'] +
            $hourlyEstimate * $weights['hourly'] +
            $historicalEstimate * $weights['historical'] +
            $dayEstimate * $weights['day_pattern']
        );
        
        $loadFactor = $this->calculateLoadFactor($features['queue_load'], $features['completion_rate']);
        $adjustedMinutes = $baseMinutes * $loadFactor;
        $estimatedMinutes = round($remainingQueues * $adjustedMinutes);
        
        $confidence = $this->calculateConfidence($features);
        $queueStatus = $this->determineQueueStatus($remainingQueues);
        
        $rangePercent = match($confidence) {
            'high' => 0.15,
            'medium' => 0.20,
            default => 0.30,
        };
        
        // Generate message
        $message = $this->generateLocalMessage($queueStatus, $estimatedMinutes, $remainingQueues, $features);
        $tips = $this->generateTips($queueStatus, $features);
        
        return [
            'estimated_minutes' => $estimatedMinutes,
            'confidence_level' => $confidence,
            'range_min' => round($estimatedMinutes * (1 - $rangePercent)),
            'range_max' => round($estimatedMinutes * (1 + $rangePercent)),
            'queue_status' => $queueStatus,
            'message' => $message,
            'tips' => $tips,
            'factors' => [
                'base_service_time' => round($baseMinutes, 1),
                'remaining_queues' => $remainingQueues,
                'load_factor' => round($loadFactor, 2),
                'queue_load' => $features['queue_load'],
                'ai_powered' => false,
            ],
        ];
    }
    
    protected function determineQueueStatus($remainingQueues)
    {
        return match(true) {
            $remainingQueues <= 5 => 'sepi',
            $remainingQueues <= 15 => 'normal',
            $remainingQueues <= 30 => 'ramai',
            default => 'sangat_ramai',
        };
    }
    
    protected function generateLocalMessage($status, $minutes, $remaining, $features)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        $timeStr = $hours > 0 ? "{$hours} jam {$mins} menit" : "{$mins} menit";
        
        $messages = [
            'sepi' => "✨ Antrian sedang sepi! Estimasi waktu tunggu Anda sekitar {$timeStr}. Anda akan segera dilayani.",
            'normal' => "👍 Antrian dalam kondisi normal dengan {$remaining} pasien di depan Anda. Estimasi waktu tunggu sekitar {$timeStr}.",
            'ramai' => "⏰ Antrian sedang ramai dengan {$remaining} pasien menunggu. Estimasi waktu tunggu Anda sekitar {$timeStr}. Mohon bersabar.",
            'sangat_ramai' => "🕐 Antrian sangat ramai hari ini dengan {$remaining} pasien di depan Anda. Estimasi waktu tunggu sekitar {$timeStr}. Terima kasih atas kesabaran Anda.",
        ];
        
        return $messages[$status] ?? $messages['normal'];
    }
    
    protected function generateTips($status, $features)
    {
        $hour = $features['hour'];
        
        $tips = match($status) {
            'sepi' => 'Waktu yang tepat untuk check-up! Pelayanan lebih cepat saat antrian sepi.',
            'normal' => 'Anda bisa menggunakan waktu tunggu untuk membaca atau mengisi formulir yang diperlukan.',
            'ramai' => 'Sebaiknya datang 10-15 menit lebih awal untuk pendaftaran. Anda juga bisa mengambil nomor antrian online untuk menghindari antri panjang.',
            'sangat_ramai' => 'Untuk kunjungan berikutnya, pertimbangkan datang saat jam tidak sibuk (pagi hari atau setelah jam makan siang).',
        };
        
        // Add time-specific tips
        if ($hour >= 10 && $hour <= 11) {
            $tips .= ' Jam 10-11 biasanya lebih ramai. Pertimbangkan datang lebih pagi next time.';
        } elseif ($hour >= 7 && $hour <= 8) {
            $tips .= ' Pagi hari adalah waktu terbaik dengan antrian lebih pendek.';
        }
        
        return $tips;
    }
    
    protected function analyzeQueueCondition($features)
    {
        $rate = $features['completion_rate'];
        
        return match(true) {
            $rate >= 0.7 => 'lancar',
            $rate >= 0.5 => 'normal',
            $rate >= 0.3 => 'lambat',
            default => 'sangat_lambat',
        };
    }
    
    protected function getTimePeriod($hour)
    {
        return match(true) {
            $hour >= 6 && $hour < 10 => 'pagi',
            $hour >= 10 && $hour < 14 => 'siang',
            $hour >= 14 && $hour < 18 => 'sore',
            default => 'malam',
        };
    }
    
    /**
     * Helper methods untuk mengambil historical data
     */
    protected function getHistoricalAverageServiceTime($queueTypeId, $days = 30)
    {
        $avg = QueueTicket::where('queue_type_id', $queueTypeId)
            ->where('status', 'DONE')
            ->where('service_date', '>=', now()->subDays($days))
            ->whereNotNull('actual_service_minutes')
            ->avg('actual_service_minutes');
        
        if (!$avg) {
            $queueType = QueueType::find($queueTypeId);
            $avg = $queueType?->avg_service_minutes ?? 10;
        }
        
        return round($avg, 1);
    }
    
    protected function getTodayAverageServiceTime($queueTypeId, $serviceDate)
    {
        $avg = QueueTicket::where('queue_type_id', $queueTypeId)
            ->whereDate('service_date', $serviceDate)
            ->where('status', 'DONE')
            ->whereNotNull('actual_service_minutes')
            ->avg('actual_service_minutes');
        
        return $avg ? round($avg, 1) : null;
    }
    
    protected function getHourlyAverageServiceTime($queueTypeId, $hour)
    {
        $avg = QueueTicket::where('queue_type_id', $queueTypeId)
            ->where('status', 'DONE')
            ->whereRaw('EXTRACT(HOUR FROM service_started_at) = ?', [$hour])
            ->where('service_date', '>=', now()->subDays(14))
            ->whereNotNull('actual_service_minutes')
            ->avg('actual_service_minutes');
        
        return $avg ? round($avg, 1) : null;
    }
    
    protected function getDayOfWeekPattern($queueTypeId, $dayOfWeek)
    {
        $avg = QueueTicket::where('queue_type_id', $queueTypeId)
            ->where('status', 'DONE')
            ->whereRaw('EXTRACT(DOW FROM service_date) = ?', [$dayOfWeek])
            ->where('service_date', '>=', now()->subWeeks(4))
            ->whereNotNull('actual_service_minutes')
            ->avg('actual_service_minutes');
        
        return $avg ? round($avg, 1) : null;
    }
    
    protected function calculateLoadFactor($queueLoad, $completionRate)
    {
        $baseFactor = 1.0;
        
        if ($queueLoad > 50) {
            $baseFactor += 0.2;
        } elseif ($queueLoad > 30) {
            $baseFactor += 0.1;
        }
        
        if ($completionRate < 0.5) {
            $baseFactor += 0.15;
        } elseif ($completionRate < 0.7) {
            $baseFactor += 0.1;
        }
        
        return $baseFactor;
    }
    
    protected function calculateConfidence($features)
    {
        $score = 0;
        
        if ($features['today_avg']) $score += 40;
        if ($features['hourly_avg']) $score += 25;
        if ($features['historical_avg'] > 0) $score += 20;
        if ($features['completed_today'] >= 10) $score += 15;
        
        return match(true) {
            $score >= 80 => 'high',
            $score >= 50 => 'medium',
            default => 'low',
        };
    }
}