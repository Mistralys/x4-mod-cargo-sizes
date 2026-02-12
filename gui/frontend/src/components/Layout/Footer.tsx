/**
 * Footer - Application footer component.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

/**
 * App footer with links and version info.
 */
export function Footer() {
  return (
    <footer className="bg-gray-800 text-gray-300 border-t border-gray-700">
      <div className="container mx-auto px-6 py-4">
        <div className="flex items-center justify-between text-sm">
          <div>
            <p>
              © 2026 X4 Cargo Sizes Mod by{' '}
              <a
                href="https://www.nexusmods.com/users/3385986"
                className="text-blue-400 hover:text-blue-300"
                target="_blank"
                rel="noopener noreferrer"
              >
                Mistralys
              </a>
            </p>
          </div>
          <div className="flex gap-4">
            <a
              href="https://github.com/Mistralys/x4-mod-cargo-sizes"
              className="text-blue-400 hover:text-blue-300"
              target="_blank"
              rel="noopener noreferrer"
            >
              GitHub
            </a>
            <a
              href="https://www.nexusmods.com/x4foundations/mods/"
              className="text-blue-400 hover:text-blue-300"
              target="_blank"
              rel="noopener noreferrer"
            >
              Nexus Mods
            </a>
            <a
              href="https://github.com/Mistralys/x4-mod-cargo-sizes/blob/main/docs/physics-tuning-guide.md"
              className="text-blue-400 hover:text-blue-300"
              target="_blank"
              rel="noopener noreferrer"
            >
              Documentation
            </a>
          </div>
        </div>
      </div>
    </footer>
  );
}
