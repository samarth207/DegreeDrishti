import os
import re

# Define university websites
universities = {
    "Amity University": "https://www.amity.edu/",
    "Manipal University": "https://www.manipal.edu/",
    "Chandigarh University": "https://www.cuchd.in/",
    "Jain University": "https://www.jainuniversity.ac.in/",
    "LPU (Lovely Professional University)": "https://www.lpu.in/",
    "Lovely Professional University": "https://www.lpu.in/",
    "Symbiosis University": "https://www.siu.edu.in/",
    "DY Patil University": "https://www.dypatil.edu/"
}

courses_dir = r"c:\Users\samth\Desktop\DD\DegreeDrishti\courses"

# Process all HTML files in the courses directory
for filename in os.listdir(courses_dir):
    if filename.endswith('.html'):
        filepath = os.path.join(courses_dir, filename)
        
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Find all table rows in the tbody
        # Pattern to match rows and add website column before Action column
        modified = False
        
        for uni_name, uni_url in universities.items():
            # Find pattern: university name, then closing </td>, eventually Action column
            # Add website link before the Action column
            
            # Pattern: Find Action button and add Website link before it
            pattern = re.compile(
                rf'(<span>{re.escape(uni_name)}</span>.*?)'
                r'(<td(?:\s+data-label="[^"]*")?>.*?</td>.*?)'
                r'(<td(?:\s+data-label="[^"]*")?>.*?</td>.*?)'
                r'(<td(?:\s+data-label="[^"]*")?>.*?</td>.*?)'
                r'(<td(?:\s+data-label="[^"]*")?>.*?</td>.*?)'
                r'(<td(?:\s+data-label="[^"]*")?><a href="[^"]*" class="btn-apply-small">Apply Now</a></td>)',
                re.DOTALL
            )
            
            def replacer(match):
                return (
                    match.group(1) +
                    match.group(2) +
                    match.group(3) +
                    match.group(4) +
                    match.group(5) +
                    f'<td data-label="Website"><a href="{uni_url}" target="_blank" rel="noopener noreferrer" class="btn-website">Visit Website</a></td>\n                            ' +
                    match.group(6)
                )
            
            new_content = pattern.sub(replacer, content)
            if new_content != content:
                content = new_content
                modified = True
        
        # Write back if modified
        if modified:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Updated {filename}")
        else:
            print(f"No changes for {filename}")

print("Done!")
