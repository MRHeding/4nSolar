# Tailwind CSS Setup for 4NSOLAR System

This project now uses a proper Tailwind CSS installation instead of the CDN for production use.

## Files Added/Modified

### New Files:
- `package.json` - Node.js dependencies and build scripts
- `tailwind.config.js` - Tailwind CSS configuration with custom theme
- `assets/css/input.css` - Source CSS file with Tailwind directives and custom components
- `assets/css/output.css` - Compiled CSS file (generated)
- `build-css.bat` - Windows build script
- `build-css.sh` - Linux/Mac build script
- `TAILWIND_SETUP.md` - This documentation

### Modified Files:
- `includes/header.php` - Updated to use local CSS instead of CDN
- `login.php` - Updated to use local CSS instead of CDN
- `print_inventory_quote.php` - Updated to use local CSS instead of CDN
- `print_quote.php` - Updated to use local CSS instead of CDN

## Setup Instructions

### 1. Install Dependencies
```bash
npm install
```

### 2. Build CSS for Development (with watch mode)
```bash
npm run build-css
```

### 3. Build CSS for Production (minified)
```bash
npm run build-css-prod
```

Or use the provided scripts:
- Windows: `build-css.bat`
- Linux/Mac: `./build-css.sh`

## Custom Theme Configuration

The Tailwind configuration includes:
- **Custom Colors:**
  - `solar-blue`: #1E40AF
  - `solar-yellow`: #FCD34D
  - `solar-green`: #059669

- **Custom Components:**
  - `.btn-primary`, `.btn-secondary`, `.btn-success`, `.btn-danger`
  - `.form-input`, `.form-select`
  - `.card`
  - `.alert-success`, `.alert-error`, `.alert-warning`, `.alert-info`

- **Custom Utilities:**
  - `.text-shadow`, `.text-shadow-lg`
  - `.bg-gradient-solar`
  - `.border-gradient`

## Development Workflow

1. Make changes to `assets/css/input.css`
2. Run the build command to compile changes
3. Refresh your browser to see changes

## Production Deployment

1. Run `npm run build-css-prod` to create minified CSS
2. Deploy the `assets/css/output.css` file with your application
3. Ensure `node_modules` is not deployed to production

## Benefits of This Setup

- ✅ **Production Ready**: No CDN dependency
- ✅ **Faster Loading**: Local CSS files load faster
- ✅ **Customizable**: Easy to add custom components and utilities
- ✅ **Optimized**: Only includes CSS classes actually used
- ✅ **Minified**: Smaller file size for production
- ✅ **Offline Capable**: Works without internet connection

## Troubleshooting

If you encounter issues:

1. **CSS not loading**: Ensure `assets/css/output.css` exists and is accessible
2. **Styles not applying**: Run the build command to regenerate CSS
3. **Missing styles**: Check that your HTML classes are included in the content paths in `tailwind.config.js`

## File Structure

```
4nsolarSystem/
├── assets/
│   └── css/
│       ├── input.css      # Source CSS with Tailwind directives
│       └── output.css     # Compiled CSS (generated)
├── includes/
│   └── header.php         # Updated to use local CSS
├── package.json           # Node.js dependencies
├── tailwind.config.js     # Tailwind configuration
├── build-css.bat          # Windows build script
├── build-css.sh           # Linux/Mac build script
└── TAILWIND_SETUP.md      # This documentation
```
