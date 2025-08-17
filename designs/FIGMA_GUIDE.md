# Figma Integration Guide

## Step 1: Export from Figma

### For Visual References:
1. Select the frame/page you want to export
2. In the export panel (right sidebar), choose:
   - **Format:** PNG (for screenshots) or JPG (for photos)
   - **Scale:** 2x for high-quality images
3. Click "Export" and save to your computer

### For Icons and Graphics:
1. Select the icon/graphic
2. Export as **SVG** for scalability
3. Save with descriptive names

### For CSS Styles:
1. Select any element in Figma
2. Right-click → "Copy CSS" or use the CSS panel
3. Paste the CSS into your stylesheets

## Step 2: Organize in Your Project

```
designs/
├── mockups/
│   ├── login-page.png
│   ├── dashboard.png
│   ├── patient-list.png
│   ├── appointment-calendar.png
│   └── billing-invoice.png
├── assets/
│   ├── icons/
│   │   ├── user.svg
│   │   ├── calendar.svg
│   │   └── billing.svg
│   └── graphics/
└── styles/
    ├── colors.css
    ├── typography.css
    └── components.css
```

## Step 3: Implementation Process

1. **Compare current pages with Figma designs**
2. **Extract color palette and typography**
3. **Identify UI components to build**
4. **Implement responsive breakpoints**
5. **Add interactive states (hover, focus, active)**

## Step 4: Share Figma File

If you want to share the actual Figma file:
1. In Figma, click "Share" (top-right)
2. Set permissions to "Anyone with the link can view"
3. Copy the link and paste it in the README

## Common Figma Export Settings

- **Images:** PNG, 2x scale
- **Icons:** SVG
- **Screenshots:** PNG, 1x scale
- **Assets for web:** Optimized for web
