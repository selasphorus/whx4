# WHx4 ACF Field Group Standards

## Naming Conventions
- Field 'key': `field_whx4_{field_name}`
- Field 'name': `whx4_{field_name}`
- Lowercase letters and underscores only
- Unique keys across the entire site

## Structure Conventions
- Match ACF's exported PHP array format
- Use `acf_add_local_field_group()`
- Provide clear 'label' and 'instructions'

## Best Practices
- Hide internal fields by prefixing 'name' with `_`
- Group fields logically by module (e.g., Horse, Monster)
- Avoid spaces, hyphens, or capital letters
- Keep field groups modular and specific
