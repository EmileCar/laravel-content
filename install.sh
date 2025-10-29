#!/bin/bash

# Laravel Content CMS Installation Script

echo "🚀 Installing Laravel Content CMS Package..."

# Publish configuration
echo "📝 Publishing configuration files..."
php artisan vendor:publish --provider="Carone\Content\CaroneContentServiceProvider" --tag="config"

# Publish migrations
echo "🗄️ Publishing database migrations..."
php artisan vendor:publish --provider="Carone\Content\CaroneContentServiceProvider" --tag="migrations"

# Run migrations
echo "⚡ Running database migrations..."
php artisan migrate

# Publish assets
echo "🎨 Publishing editor assets (CSS/JS)..."
php artisan vendor:publish --provider="Carone\Content\CaroneContentServiceProvider" --tag="assets"

# Publish views (optional)
read -p "🖼️ Do you want to publish views for customization? (y/N): " publish_views
if [[ $publish_views =~ ^[Yy]$ ]]; then
    php artisan vendor:publish --provider="Carone\Content\CaroneContentServiceProvider" --tag="views"
    echo "✅ Views published to resources/views/vendor/laravel-content/"
fi

echo ""
echo "✅ Installation complete!"
echo ""
echo "📚 Next steps:"
echo "1. Configure middleware in config/content.php"
echo "2. Set up authorization gates (see README.md)"
echo "3. Visit /admin/content/ to start creating content"
echo ""
echo "📖 Documentation:"
echo "- README.md - General usage and API"
echo "- EDITOR-GUIDE.md - Web editor documentation"
echo ""
echo "🎉 Happy content managing!"