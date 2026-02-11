# Plan: Release Notes Generator from Changelogs

## Summary
Create a release notes generator that extracts the latest version information from both `changelog.md` and `changelog-builder.md` using the `mistralys/changelog-parser` library, and saves formatted release notes to `/build/release-notes-v{VERSION}.md` during the build process.

## Approach / Architecture
The solution follows the existing **Reference Renderer Pattern** used by `MarkdownReference` and `BBCodeReference`. A new `ReleaseNotesGenerator` class will be created in the `References` namespace, integrated into the build workflow via `CargoSizeExtractor::writeReferenceFiles()`.

### High-Level Flow
```
Build Process
    ↓
CargoSizeExtractor::writeFiles()
    ↓
writeReferenceFiles()
    ↓
├─ writeMarkdownReference()
├─ writeNexusBBCodeReference()
└─ writeReleaseNotes() [NEW]
    ↓
ReleaseNotesGenerator [NEW]
    ├─ Parse changelog.md → get version + changes
    ├─ Parse changelog-builder.md → get version + changes
    ├─ Format as Markdown
    └─ Save to build/release-notes-v{VERSION}.md
```

## Rationale
1. **Existing Pattern**: Follows the established `References` namespace pattern (MarkdownReference, BBCodeReference)
2. **Library Usage**: `changelog-parser` is already a dependency and used in `CargoSizeBuildTools::updateVersion()`
3. **Build Integration**: Natural fit in `writeReferenceFiles()` alongside other documentation generators
4. **File Naming**: Version-specific filename enables tracking release notes across builds
5. **Dual Changelog Support**: Separates mod content changes from build tool changes for clarity

## Detailed Steps

### Step 1: Create ReleaseNotesGenerator Class
**Location**: `src/Mods/CargoSizesMod/References/ReleaseNotesGenerator.php`

**Responsibilities**:
- Parse both changelog files using `ChangelogParser::parseMarkdownFile()`
- Extract latest version from primary changelog (`changelog.md`)
- Find matching version in builder changelog (`changelog-builder.md`)
- Format output with proper Markdown structure
- Append installation instructions footer (AIO/FOMOD explanation)
- Handle missing versions gracefully (builder changelog may not have matching version)
- Save to `build/release-notes-v{VERSION}.md`

**Key Methods**:
```php
public function __construct(FolderInfo $buildFolder)
public function generate(): void
private function parseChangelog(string $path): ?ChangelogVersion
private function formatMainChangelog(ChangelogVersion $version): string
private function formatBuilderChangelog(?ChangelogVersion $version): string
private function formatFooter(): string
private function getOutputPath(string $version): string
```

### Step 2: Integrate into Build Process
**Location**: `src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php`

**Changes**:
- Add `use` statement for `ReleaseNotesGenerator`
- Add private method `writeReleaseNotes()` in `CargoSizeExtractor`
- Call `writeReleaseNotes()` from existing `writeReferenceFiles()` method
- Add console output header "Writing release notes"

### Step 3: Implement Markdown Formatting
**Format Specification**:
```markdown
# Release v{VERSION} - {TITLE}

{CHANGES FROM changelog.md}

## Builder v{VERSION} - {TITLE}

{CHANGES FROM changelog-builder.md}

----

Choose your ZIP file for installing manually or via Vortex.

AIO = All In One, with all supported ship types
FOMOD = Installer to choose by ship type and size
```

**Change Rendering**:
- List items as bullet points (already in changelog format)
- Preserve categorization if present
- Include all change text exactly as written
- Append installation instructions footer after builder changelog section

### Step 4: Error Handling
**Scenarios**:
1. **Primary changelog missing**: Throw `CargoSizeException` (build should fail)
2. **Builder changelog missing**: Log warning, generate notes without builder section
3. **No matching builder version**: Generate notes with primary changelog only
4. **Parse errors**: Propagate `ChangelogParserException` upward

### Step 5: Testing Strategy
**Manual Testing**:
1. Run `composer build` and verify release notes file created
2. Check file naming matches version from `mod-version.txt`
3. Verify both changelog sections present when versions match
4. Test with mismatched versions (mod at 3.0.0, builder at 1.4.0)
5. Verify Markdown formatting is valid

**Integration Points to Verify**:
- Build folder path resolution (uses existing `$this->outputFolder`)
- Version number consistency with `ModInfo::getVersion()`
- Console output formatting matches existing style

## Dependencies
- **Existing**: `mistralys/changelog-parser` (already installed)
- **Existing**: `AppUtils\FileHelper\FolderInfo` (already used)
- **Existing**: `Mistralys\ChangelogParser\ChangelogParser` (already imported in CargoSizeBuildTools)

## Required Components
### New Files
- `src/Mods/CargoSizesMod/References/ReleaseNotesGenerator.php`

### Modified Files
- `src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php` (add integration)

### Input Files (Existing)
- `changelog.md` (project root)
- `changelog-builder.md` (project root)

### Output Files
- `build/v{MOD_VERSION}-for-v{GAME_VERSION}/release-notes-v{MOD_VERSION}.md` (or similar location in build folder)

**Note**: Output location should match the versioned build folder structure already established by `writeFiles()`.

## Assumptions
1. Both changelogs follow the format supported by `changelog-parser`
2. Latest version in `changelog.md` matches version in `mod-version.txt`
3. Builder changelog may have a different latest version (this is acceptable)
4. Release notes should be generated per build, not cumulative
5. Console output follows existing `Console::header()` / `Console::line1()` patterns
6. Build folder structure already exists when `writeReferenceFiles()` is called

## Constraints
### Architectural
- **Must** extend or follow existing Reference pattern
- **Must** use synchronous file I/O only
- **Must** have strict type declarations (`declare(strict_types=1)`)
- **Must** use proper namespacing: `Mistralys\X4\Mods\CargoSizesMod\References`
- **Must** follow existing error handling patterns (exceptions)

### File I/O
- **Must** use `FolderInfo` and `FileInfo` from AppUtils
- **Must** use synchronous file operations only (no async)
- **Must** handle file write failures gracefully

### Naming Conventions
- Class name: `ReleaseNotesGenerator` (noun, descriptive)
- Method names: `generate()`, `parseChangelog()`, etc. (verb-based)
- File name: `release-notes-v{VERSION}.md` (kebab-case, versioned)

### Integration
- **Must** be called from `CargoSizeExtractor::writeReferenceFiles()`
- **Must** use same `Console` output style as existing code
- **Must** not break existing build process if it fails (decide: exception vs warning)

## Out of Scope
- HTML generation of release notes
- Multi-version release notes (cumulative changelog)
- Automatic GitHub release creation
- Email notifications
- Changelog validation or linting
- Comparison between versions
- Change categorization beyond what's in the changelogs
- Localization of release notes (English only)
- BBCode or rich text formatting

## Acceptance Criteria
1. ✅ Running `composer build` generates `release-notes-v{VERSION}.md` in build folder
2. ✅ Release notes contain title from `changelog.md` as `# Release v{VERSION} - {TITLE}`
3. ✅ Release notes contain changes from `changelog.md` under main heading
4. ✅ Release notes contain builder changelog as `## Builder v{VERSION} - {TITLE}`
5. ✅ Release notes contain changes from `changelog-builder.md` under builder heading
6. ✅ Release notes contain installation instructions footer (AIO/FOMOD explanation)
7. ✅ File naming uses actual version number (e.g., `release-notes-v3.0.0.md`)
8. ✅ Console output shows "Writing release notes" header during build
9. ✅ Generated Markdown is syntactically valid
10. ✅ Build succeeds even if builder changelog has different version
11. ✅ Build succeeds even if builder changelog is missing (with warning)

## Testing Strategy
### Pre-Implementation Verification
1. Verify `changelog-parser` is installed and accessible
2. Confirm existing changelog files parse correctly
3. Verify build folder paths are correctly resolved

### Unit Testing (Optional)
- Test changelog parsing with mock changelog data
- Test Markdown formatting output
- Test error handling for missing files
- Test version extraction accuracy

### Integration Testing (Required)
1. **Full build test**: Run `composer build` from clean state
2. **Output verification**: Manually inspect generated `release-notes-*.md`
3. **Content accuracy**: Compare release notes to source changelogs
4. **Footer verification**: Confirm AIO/FOMOD instructions appear at end
5. **Version consistency**: Verify version number matches `mod-version.txt`
6. **Error handling**: Test with missing/malformed changelog files
7. **Builder version mismatch**: Test when builder has different version

### Regression Testing
1. Verify existing reference files still generate correctly
2. Verify build process completes without errors
3. Verify ZIP files and FOMOD installer still build correctly
4. Run PHPUnit tests: `composer test`
5. Run PHPStan analysis: `composer analyze`

## Risks & Mitigations

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| **Changelog parser fails** | Build stops | Low | Wrap in try-catch, use `ChangelogParser::parseMarkdownFile()` which handles errors |
| **Builder changelog missing** | Incomplete release notes | Medium | Check file existence, generate partial release notes with warning |
| **Version mismatch** | Confusing release notes | Medium | Document clearly that builder may have different version, use separate headings |
| **Build folder not created** | File write fails | Low | `writeReferenceFiles()` called after build folders created, path already validated |
| **Changelog format invalid** | Parse fails | Low | Use standard markdown format already validated by existing usage |
| **Performance impact** | Build slower | Very Low | Parsing two small files is negligible (<10ms) |
| **Breaking existing build** | Build fails | Low | Add new method call last in sequence, test thoroughly before commit |
| **Incorrect file path** | File saved to wrong location | Medium | Use existing `$this->outputFolder` pattern, match reference file logic |

## Post-Implementation Checklist
- [ ] Code follows constraints.md rules (strict types, sync I/O, naming)
- [ ] PHPUnit tests pass (`composer test`)
- [ ] PHPStan analysis passes (`composer analyze`)
- [ ] Release notes file generated successfully
- [ ] Content matches both changelogs accurately
- [ ] Console output is clear and informative
- [ ] Error handling tested (missing files, parse errors)
- [ ] Manifest documents updated:
  - [ ] `public-api.md` (add ReleaseNotesGenerator signature)
  - [ ] `file-tree.md` (add new class to References directory)
  - [ ] `data-flows.md` (add release notes generation flow)
  - [ ] Update "Last Updated" date
- [ ] Git commit with clear message

---

**AGENT**: Planning  
**STATUS**: READY_FOR_PM
