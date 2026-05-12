# Upload Validation Fix TODO

- [x] Understand current upload flow and identify mismatch gap (`adminside/upload_crew_documents.php`)
- [x] Confirm intended behavior with user (reject upload when filename crew info does not match selected crew)
- [x] Add strict backend filename vs selected crew validation in `adminside/upload_crew_documents.php`
- [x] Improve crew matching reliability in `identifyCrewFromFilename()`
- [x] Update UX reminder text in `adminside/crew_upload.php`
- [ ] Run PHP lint checks for edited files (PHP CLI not available in current environment)
- [x] Mark completion summary
