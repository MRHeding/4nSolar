#!/bin/bash
echo "Building Tailwind CSS for production..."
npx tailwindcss -i ./assets/css/input.css -o ./assets/css/output.css --minify
echo "CSS build complete!"
