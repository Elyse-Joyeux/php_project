import string

text = "eyvpufvusapuhktwynb"
alphabet = string.ascii_lowercase

for shift in range(26):
    result = ""
    for c in text:
        result += alphabet[(alphabet.index(c) - shift) % 26]
    print(shift, result)