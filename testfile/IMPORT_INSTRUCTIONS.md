# 📚 Paano I-import ang Questions sa Database

## Simple Steps:

### 1. I-open ang browser
Buksan ang browser (Chrome, Firefox, etc.)

### 2. I-run ang import script
Type sa address bar:
```
http://localhost/php-project/import_questions.php
```

### 3. Tapos na!
Makikita mo ang success message kung successful ang import.

---

## Ano ang Ginagawa ng Script?

1. **Lumilikha ng Tables** - Automatic na gagawa ng:
   - `exam_categories` - Para sa exam categories
   - `questions` - Para sa mga tanong
   - `question_options` - Para sa mga sagot (A, B, C, D, E)

2. **Nag-import ng Questions** - Babasahin ang CSV file:
   - Location: `crewside/csv/deck-management-drycargo.csv`
   - Total: 48 questions
   - Category: DECK - MANAGEMENT - DRY CARGO

3. **Nag-save sa Database** - Lahat ng questions at answers ay ma-save sa MySQL

---

## Mga Files na Ginawa:

✅ **import_questions.php** - Main import script (gamitin ito!)
✅ **database/exam_tables.sql** - Database schema
✅ **setup_exam_tables.php** - Alternative setup script
✅ **import_deck_questions.php** - Detailed import script with logging

---

## Troubleshooting:

### Kung may error:
1. Check kung running ang XAMPP/MySQL
2. Check kung tama ang database name sa `config/database.php`
3. Check kung existing ang CSV file sa `crewside/csv/deck-management-drycargo.csv`

### Kung gusto mo ulit i-import:
- Pwede mo ulit i-run ang `import_questions.php`
- Automatic na buburahin ang old questions at mag-import ng bago

---

## Next Steps:

After successful import, pwede mo na gamitin ang questions sa examination system!

Ang questions ay naka-save na sa database at ready na para sa:
- Examination page (`crewside/examination.php`)
- Quiz system
- Testing ng crew members

---

## Database Tables Created:

```sql
exam_categories
├── id
├── department (DECK, ENGINE, STEWARD)
├── category (MANAGEMENT, OPERATIONAL, etc.)
├── vessel_type (DRY CARGO, TANKER, etc.)
└── total_questions

questions
├── id
├── exam_category_id
├── question_id (CFGH, EWRW, etc.)
├── question_text
└── question_order

question_options
├── id
├── question_id
├── option_letter (A, B, C, D, E)
├── option_text
└── is_correct (TRUE/FALSE)
```

---

## Support:

Kung may tanong, check ang:
- `EXAM_IMPORT_TODO.md` - Para sa progress tracking
- `database/exam_tables.sql` - Para sa database structure
