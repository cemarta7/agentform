# 🚀 Redis & Horizon Compatibility Guide

## 🎯 **Overview**

This guide ensures the AgentForm dashboard works correctly with both **Database** and **Redis/Horizon** queue configurations.

## 🔧 **Environment Setup**

### For Redis/Horizon Configuration:

1. **Install Redis Extension**:
   ```bash
   # macOS with Homebrew
   brew install redis
   pecl install redis
   
   # Ubuntu/Debian
   sudo apt-get install redis-server php-redis
   
   # Add to php.ini
   extension=redis
   ```

2. **Start Redis Server**:
   ```bash
   redis-server
   # Or as service: brew services start redis
   ```

3. **Configure Environment**:
   ```env
   QUEUE_CONNECTION=redis
   CACHE_STORE=redis
   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
   REDIS_PASSWORD=null
   ```

4. **Install Horizon**:
   ```bash
   composer require laravel/horizon
   php artisan horizon:install
   php artisan migrate
   ```

### For Database Configuration:
```env
QUEUE_CONNECTION=database
CACHE_STORE=database
```

## 🧪 **Testing Redis/Horizon Functionality**

### 1. **Basic Redis Test**:
```bash
# Test Redis connection
redis-cli ping
# Should return: PONG

# Test Laravel Redis
php artisan tinker
>>> Redis::connection()->ping();
# Should return: "+PONG"
```

### 2. **Queue Configuration Test**:
```bash
# Check current queue configuration
php artisan config:show queue.default

# Test queue with Redis
QUEUE_CONNECTION=redis php artisan queue:work --once

# Test queue with database
QUEUE_CONNECTION=database php artisan queue:work --once
```

### 3. **Horizon Metrics Test**:
```bash
# Start Horizon (Redis only)
php artisan horizon

# In another terminal, test metrics
curl -s "http://localhost:8000/api/metrics/horizon" | jq '.queue_sizes'
```

## 📊 **Expected Behavior**

### With Redis/Horizon:
- ✅ Queue sizes show real-time Redis queue data
- ✅ Horizon dashboard available at `/horizon`
- ✅ Advanced metrics (wait times, throughput)
- ✅ Real-time job monitoring

### With Database Queues:
- ✅ Queue sizes show database job counts
- ✅ Basic metrics and success rates
- ✅ Fallback functionality maintained
- ⚠️ No Horizon dashboard (expected)

## 🔄 **Queue Job Flow**

### Redis/Horizon Flow:
```
AgentForm Created
    ↓
VerifyEmailJob → Redis 'verification' queue
    ↓ (on success)
SendWelcomeEmailJob → Redis 'email' queue
    ↓
Horizon processes jobs
    ↓
Metrics updated in real-time
```

### Database Flow:
```
AgentForm Created
    ↓
VerifyEmailJob → Database 'verification' queue
    ↓ (on success)
SendWelcomeEmailJob → Database 'email' queue
    ↓
Queue worker processes jobs
    ↓
Metrics calculated from database
```

## 🛠 **HorizonMetricsService Compatibility**

The service automatically detects and adapts:

```php
// Automatic detection
$isRedis = $service->isUsingRedis();        // true/false
$hasHorizon = $service->isHorizonAvailable(); // true/false

// Queue size detection
if ($isRedis && $hasHorizon) {
    // Use Horizon JobRepository
    $size = $jobRepository->getPending()->count();
} else {
    // Use database query
    $size = DB::table('jobs')->where('queue', $queue)->count();
}
```

## 🚀 **Testing Commands**

### Create Test Data:
```bash
# Generate forms and jobs
php artisan test:agent-form-jobs --count=20

# Check queue distribution
php artisan tinker --execute="
DB::table('jobs')->select('queue', DB::raw('count(*) as count'))
->groupBy('queue')->get()->each(function(\$row) { 
    echo \$row->queue . ': ' . \$row->count . PHP_EOL; 
});
"
```

### Process Jobs:
```bash
# Redis/Horizon
php artisan horizon &
# Jobs process automatically

# Database
php artisan queue:work --queue=verification,email,default --verbose
```

### Monitor Metrics:
```bash
# Real-time dashboard metrics
curl -s "http://localhost:8000/api/metrics/horizon" | jq '.'

# AgentForm statistics
curl -s "http://localhost:8000/api/metrics/agentform" | jq '.'
```

## 📈 **Dashboard Verification**

### Test Different Queue States:

1. **Empty Queues**:
   ```json
   {
     "queue_sizes": {
       "default": 0,
       "verification": 0,
       "email": 0
     }
   }
   ```

2. **Active Processing**:
   ```json
   {
     "queue_sizes": {
       "default": 0,
       "verification": 45,
       "email": 12
     }
   }
   ```

3. **Mixed States**:
   ```json
   {
     "queue_sizes": {
       "default": 2,
       "verification": 0,
       "email": 8
     }
   }
   ```

## 🔧 **Troubleshooting**

### Redis Extension Issues:
```bash
# Check if Redis extension is loaded
php -m | grep redis

# Check Redis configuration
php --ini | grep redis

# Test Redis class availability
php -r "var_dump(class_exists('Redis'));"
```

### Horizon Issues:
```bash
# Clear Horizon data
php artisan horizon:clear

# Restart Horizon
php artisan horizon:terminate
php artisan horizon

# Check Horizon status
php artisan horizon:status
```

### Queue Issues:
```bash
# Clear failed jobs
php artisan queue:clear

# Restart queue workers
php artisan queue:restart

# Check queue status
php artisan queue:monitor
```

## 🎯 **Production Recommendations**

### Redis/Horizon Setup:
- ✅ Use Redis for high-throughput applications
- ✅ Enable Horizon for advanced monitoring
- ✅ Configure Redis persistence
- ✅ Set up Redis clustering for scale

### Database Setup:
- ✅ Use for simpler applications
- ✅ Easier deployment (no Redis dependency)
- ✅ Good for development/testing
- ✅ Reliable fallback option

## 🔒 **Security Considerations**

### Redis:
- Configure Redis authentication
- Bind to specific interfaces
- Use SSL/TLS for remote connections
- Regular security updates

### Database:
- Standard database security practices
- Regular backups
- Connection encryption

## ✅ **Verification Checklist**

- [ ] Redis server running (if using Redis)
- [ ] PHP Redis extension installed (if using Redis)
- [ ] Horizon installed and configured (if using Redis)
- [ ] Queue workers running
- [ ] API endpoints accessible
- [ ] Dashboard showing different queue sizes
- [ ] Real-time updates working (15-second refresh)
- [ ] Job processing working correctly
- [ ] Metrics calculation accurate

## 🚨 **Known Issues & Solutions**

### Issue: "Class Redis not found"
**Solution**: Install PHP Redis extension or switch to database queues

### Issue: Same queue sizes showing
**Solution**: Check authentication on API endpoints, verify job distribution

### Issue: Horizon not starting
**Solution**: Ensure Redis is running, check Horizon configuration

### Issue: Jobs not processing
**Solution**: Start queue workers, check job table/Redis queues

---

This compatibility guide ensures the AgentForm dashboard works seamlessly with both Redis/Horizon and database queue configurations, providing a robust monitoring solution regardless of the underlying queue infrastructure. 
