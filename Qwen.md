# 05 – Admin Panel (Filament)

## Resources

### ProductResource

- **Form Sections**: Basic Info, Price & Sale, Inventory & SKU, Variants (volume‑based), Fragrance Profile, Images, SEO
- **List Columns**: Thumb, Name (slug), Price/Sale, SKU, Category, Brand, Featured, Stock badge, Qty, UpdatedAt
- **Filters**: Category, Brand, Featured, Active, Stock status, Has variants, Price range
- **Actions**: Preview PDP, Edit, Duplicate, Delete (soft), Publish/Unpublish, Feature/Unfeature
- **Hooks**: convert yen→cents, slugify, Meili reindex, image derivatives

### BrandResource / CategoryResource

- Slug unique; parent for categories; product counts; reindex on change

### OrderResource

- **List**: order_number, customer, totals, status, date, items_count, delivered_on
- **View Tabs**: Items | Shipping | Payments | Timeline
- **Transitions**: ordered→processing→shipped→delivered (cancel/refund branches)
- **Actions**: Mark shipped/delivered, Cancel, Refund (mock)

### ShipmentResource (or relation manager)

- carrier, tracking_number, status, shipped_at/delivered_at, timeline display

### SliderResource

- image, tagline, title, subtitle, link, active, schedule (starts/ends), sort

### CouponResource

- code, type(percent/fixed), value, active window, usage caps

### UserResource

- avatar+name, email (verified), phone, role, status, orders count; disable/enable, reset link

### Settings Page

- profile (name/mobile/email), password change, security logs

## Policies & Roles

- Roles: admin, staff (limited), viewer (read-only)
- Gate sensitive actions (cancel/refund, user disable)
- Audit trail: who edited what and when
