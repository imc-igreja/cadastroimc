import pdfplumber

with pdfplumber.open('carteirinha_exemplo.pdf') as pdf:
    p = pdf.pages[0]
    print(f'Dimensoes: {p.width} x {p.height}')
    print('\n--- PALAVRAS ---')
    words = p.extract_words()
    for w in words:
        print(f'  [{w["text"]}] x0={w["x0"]:.1f} top={w["top"]:.1f} x1={w["x1"]:.1f} bot={w["bottom"]:.1f}')
    print('\n--- RECTS ---')
    for r in p.rects:
        print(f'  x0={r["x0"]:.1f} top={r["top"]:.1f} x1={r["x1"]:.1f} bot={r["bottom"]:.1f} w={r["x1"]-r["x0"]:.1f} h={r["bottom"]-r["top"]:.1f}')
