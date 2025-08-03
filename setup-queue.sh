#!/bin/bash

echo "🔧 Setting up Laravel Queue System..."
echo "=================================="

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Please run this script from the Laravel root directory"
    exit 1
fi

echo "1. Running migrations to ensure jobs table exists..."
php artisan migrate --force

echo "2. Clearing cache and config..."
php artisan config:clear
php artisan cache:clear

echo "3. Testing queue configuration..."
php artisan queue:status

echo "4. Dispatching test job..."
php artisan queue:test

echo "5. Checking queue status after test..."
php artisan queue:status

echo ""
echo "✅ Queue setup complete!"
echo ""
echo "📋 Next Steps:"
echo "=============="
echo "1. Start the queue worker:"
echo "   php artisan queue:work --daemon"
echo ""
echo "2. Or run queue worker in the background:"
echo "   nohup php artisan queue:work --daemon > storage/logs/queue.log 2>&1 &"
echo ""
echo "3. Monitor queue status:"
echo "   php artisan queue:status"
echo ""
echo "4. Test email job:"
echo "   php artisan queue:test --type=email"
echo ""
echo "5. View logs:"
echo "   tail -f storage/logs/laravel.log"
echo "   tail -f storage/logs/queue.log"
