import os
import zipfile

# Use portable relative paths instead of hardcoded drives
workspace_dir = os.path.dirname(os.path.abspath(__file__))
zip_path = os.path.join(os.path.dirname(workspace_dir), "google-login.zip")

files_to_include = [
    "google-login.php",
    "composer.json",
    "LICENSE",
    "README.md",
    "INSTALL.md",
    "DEVELOPMENT.md"
]

folders_to_include = [
    "src",
    "templates",
    "assets",
    "vendor"
]

print(f"Starting to zip plugin files from: {workspace_dir}")

with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
    # Add root files
    for file in files_to_include:
        file_path = os.path.join(workspace_dir, file)
        if os.path.exists(file_path):
            zipf.write(file_path, os.path.join("google-login", file))
            print(f"Added file: {file}")
            
    # Add folders recursively
    for folder in folders_to_include:
        folder_path = os.path.join(workspace_dir, folder)
        if os.path.exists(folder_path):
            for root, dirs, files in os.walk(folder_path):
                for file in files:
                    full_path = os.path.join(root, file)
                    rel_path = os.path.relpath(full_path, workspace_dir)
                    zipf.write(full_path, os.path.join("google-login", rel_path))
            print(f"Added folder: {folder}")

print(f"Zipping complete. Created package at: {zip_path} successfully!")
