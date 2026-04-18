#!/usr/bin/env python3
"""
gerar_carteirinha.py

Preenche os campos da carteirinha ministerial diretamente sobre o
PDF modelo (carteirinha_exemplo.pdf), sem recriar o design.

Dependencias: reportlab, pypdf, Pillow
Uso: python gerar_carteirinha.py <arquivo.json>

@package  CarteirinhaMinisterial
@author   Igreja Missoes em Cristo
"""
import sys, json, os, io
from datetime import datetime

from reportlab.pdfgen import canvas
from reportlab.lib import colors
from reportlab.lib.utils import ImageReader
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from pypdf import PdfReader, PdfWriter

BASE_DIR  = os.path.dirname(os.path.abspath(__file__))
MODELO    = os.path.join(BASE_DIR, 'carteirinha_exemplo.pdf')

# ── Dimensões do modelo (pt) ───────────────────────────────────────
PW = 768.0
PH = 543.0

# ── Cores ──────────────────────────────────────────────────────────
BRANCO      = colors.white
CINZA_CLARO = colors.HexColor('#F0F0F0')
TEXTO_ESC   = colors.HexColor('#1a1a1a')

# ── Registrar Montserrat se disponível ────────────────────────────
def _reg_fonte(nome, arquivo):
    caminho = os.path.join(BASE_DIR, arquivo)
    if os.path.exists(caminho):
        try:
            pdfmetrics.registerFont(TTFont(nome, caminho))
            return True
        except Exception:
            pass
    return False

_reg_fonte('Montserrat',      'Montserrat-Regular.ttf')
_reg_fonte('Montserrat-Bold', 'Montserrat-Bold.ttf')

def _fonte(bold=False):
    nome = 'Montserrat-Bold' if bold else 'Montserrat'
    try:
        pdfmetrics.getFont(nome)
        return nome
    except Exception:
        return 'Helvetica-Bold' if bold else 'Helvetica'

# ── Helpers ────────────────────────────────────────────────────────
def fmt_data(d):
    if not d:
        return ''
    try:
        return datetime.strptime(str(d)[:10], '%Y-%m-%d').strftime('%d/%m/%Y')
    except Exception:
        return str(d)

def trunc(txt, n):
    txt = str(txt or '')
    return txt if len(txt) <= n else txt[:n-1] + '…'

def escrever(c, texto, x, y, size=7, bold=False, cor=BRANCO, align='left'):
    """Escreve texto na camada de sobreposição."""
    c.saveState()
    c.setFillColor(cor)
    c.setFont(_fonte(bold), size)
    if align == 'center':
        c.drawCentredString(x, y, texto)
    elif align == 'right':
        c.drawRightString(x, y, texto)
    else:
        c.drawString(x, y, texto)
    c.restoreState()

# ── Mapeamento de campos (coordenadas em pt, origem inferior-esq.) ─
#
# Convertido do pdfplumber (origem superior):  y_pdf = PH - y_plumber
#
# FRENTE (card esquerdo x: 146–374)
#   Campo amarelo Registro:  centro x=248.9  y_plumber≈284  → y=259
#   Campo Nome:              centro x=257.0  y_plumber≈314  → y=229
#   Campo Cargo:             centro x=206.1  y_plumber≈345  → y=198
#   Campo Registro (azul):   centro x=307.9  y_plumber≈345  → y=198
#   Campo RG:                centro x=206.6  y_plumber≈379  → y=164
#   Campo Ordenação:         centro x=307.9  y_plumber≈379  → y=164
#   Foto:                    x=164.6  y_base=258  w=40  h=30
#
# VERSO (card direito x: 399–633)
#   Campo Nacionalidade:     centro x=466.4  y_plumber≈279  → y=264
#   Campo Naturalidade:      centro x=572.1  y_plumber≈279  → y=264
#   Campo Validade:          centro x=466.4  y_plumber≈316  → y=227
#   Campo Estado Civil:      centro x=572.1  y_plumber≈315  → y=228
#   Presidente nome:         centro x=517.0  y_plumber≈380  → y=163
#   Presidente cargo:        centro x=517.0  y_plumber≈389  → y=154

def criar_camada(d):
    """Cria um PDF em memória com apenas os textos/foto sobre fundo transparente."""
    buf = io.BytesIO()
    c = canvas.Canvas(buf, pagesize=(PW, PH))

    # ── FRENTE ─────────────────────────────────────────────────────

    # Foto - ajustada para posicionamento correto no retângulo central
    foto_path = d.get('foto', '')
    if foto_path and os.path.exists(foto_path):
        try:
            from PIL import Image
            
            # Abre a imagem para obter dimensões originais
            pil_img = Image.open(foto_path)
            img_width, img_height = pil_img.size
            
            # Dimensões da área da foto na carteirinha (espaço do avatar, lado esquerdo)
            target_width = 46
            target_height = 48
            
            # Calcula o aspect ratio
            img_ratio = img_width / img_height
            target_ratio = target_width / target_height
            
            # Ajusta dimensões mantendo proporção e preenchendo a área
            if img_ratio > target_ratio:
                # Imagem mais larga - ajusta pela altura
                new_height = target_height
                new_width = new_height * img_ratio
                x_offset = (new_width - target_width) / 2
                y_offset = 0
            else:
                # Imagem mais alta - ajusta pela largura
                new_width = target_width
                new_height = new_width / img_ratio
                x_offset = 0
                y_offset = (new_height - target_height) / 2
            
            # Posição da foto (área do avatar, lado esquerdo da frente)
            foto_x = 293
            foto_y = 246
            
            # Raio dos cantos arredondados
            corner_radius = 5

            # Desenha a imagem com crop centralizado e cantos arredondados
            img = ImageReader(foto_path)
            c.saveState()

            # Clip usando roundRect (método correto do ReportLab)
            p = c.beginPath()
            p.roundRect(foto_x, foto_y, target_width, target_height, corner_radius)
            c.clipPath(p, stroke=0, fill=1)

            c.drawImage(img,
                        foto_x - x_offset, foto_y - y_offset,
                        width=new_width, height=new_height,
                        mask='auto')
            c.restoreState()
        except Exception as e:
            print(f"Erro ao processar foto: {e}")
            import traceback
            traceback.print_exc()
            pass

    # ── QR CODE dinâmico (verso) ───────────────────────────────────
    # Sobrepõe o QR estático do template com um QR gerado com a URL da carteirinha
    url_qr = d.get('url_carteirinha', '')
    if url_qr:
        try:
            import qrcode
            from PIL import Image as PilImage

            qr = qrcode.QRCode(
                version=1,
                error_correction=qrcode.constants.ERROR_CORRECT_M,
                box_size=3,
                border=1,
            )
            qr.add_data(url_qr)
            qr.make(fit=True)
            qr_img = qr.make_image(fill_color='black', back_color='white').convert('RGB')

            # Salva em buffer para o ReportLab ler
            qr_buf = io.BytesIO()
            qr_img.save(qr_buf, format='PNG')
            qr_buf.seek(0)

            # Posição do QR no verso — sobre o QR estático pequeno
            # QR pequeno fica ao lado do texto legal, lado direito
            qr_x = 558
            qr_y = 190
            qr_size = 18

            c.drawImage(ImageReader(qr_buf), qr_x, qr_y, width=qr_size, height=qr_size)
        except Exception as e:
            print(f"Erro ao gerar QR code: {e}")

    # Registro (campo amarelo) — centralizado no campo x:216–282
    # y_plumber rect: top=276.6 bot=292.5 → centro=284.5 → y_pdf=543-284.5=258.5
    escrever(c, trunc(d.get('registro', ''), 10),
             x=249, y=256, size=7.5, bold=True, cor=TEXTO_ESC, align='center')

    # Nome — campo x:162–351, y_top=304 y_bot=323 → y_pdf centro=229
    # alinhado à esquerda com padding de 8pt
    escrever(c, trunc(d.get('nome', ''), 32),
             x=170, y=226, size=7, bold=False, cor=BRANCO)

    # Cargo — campo x:162–250, centro y=198
    escrever(c, trunc(d.get('cargo', ''), 13),
             x=170, y=195, size=6.5, bold=False, cor=CINZA_CLARO)

    # Registro (campo azul frente) — campo x:264–351, centro y=198
    escrever(c, trunc(d.get('registro', ''), 8),
             x=272, y=195, size=6.5, bold=False, cor=CINZA_CLARO)

    # RG — campo x:163–250, centro y=164
    escrever(c, trunc(d.get('rg', ''), 13),
             x=170, y=161, size=6.5, bold=False, cor=CINZA_CLARO)

    # Ordenação — campo x:264–351, centro y=164
    escrever(c, fmt_data(d.get('data_ordenacao', '')),
             x=272, y=161, size=6.5, bold=False, cor=CINZA_CLARO)

    # ── VERSO ──────────────────────────────────────────────────────

    # Nacionalidade — campo x:423–510, centro y=264
    escrever(c, trunc(d.get('nacionalidade', 'Brasileira'), 13),
             x=430, y=261, size=6.5, bold=False, cor=CINZA_CLARO)

    # Naturalidade — campo x:529–616, centro y=264
    # fonte menor para caber cidades com nomes longos
    escrever(c, trunc(d.get('naturalidade', ''), 18),
             x=536, y=261, size=6, bold=False, cor=CINZA_CLARO)

    # Validade — campo x:423–510, centro y=227
    escrever(c, fmt_data(d.get('data_validade', '')),
             x=430, y=224, size=6.5, bold=False, cor=CINZA_CLARO)

    # Estado Civil — campo x:529–616, centro y=228
    escrever(c, trunc(d.get('estado_civil', ''), 13),
             x=536, y=225, size=6.5, bold=False, cor=CINZA_CLARO)

    # Presidente e cargo já estão fixos no modelo — não inserir

    c.save()
    buf.seek(0)
    return buf


def gerar(dados_json: str):
    d = json.loads(dados_json)

    saida = d.get('saida', 'carteirinha.pdf')
    os.makedirs(os.path.dirname(os.path.abspath(saida)), exist_ok=True)

    if not os.path.exists(MODELO):
        raise FileNotFoundError(f'Modelo não encontrado: {MODELO}')

    # Lê o PDF modelo
    reader_modelo = PdfReader(MODELO)
    pagina_modelo = reader_modelo.pages[0]

    # Cria a camada de texto
    camada_buf = criar_camada(d)
    reader_camada = PdfReader(camada_buf)
    pagina_camada = reader_camada.pages[0]

    # Mescla: modelo embaixo, textos em cima
    pagina_modelo.merge_page(pagina_camada)

    writer = PdfWriter()
    writer.add_page(pagina_modelo)

    with open(saida, 'wb') as f:
        writer.write(f)

    print(f'PDF gerado: {saida}')


# ──────────────────────────────────────────────────────────────────
if __name__ == '__main__':
    if len(sys.argv) < 2:
        print('Uso: python gerar_carteirinha.py <arquivo.json>')
        sys.exit(1)
    arg = sys.argv[1]
    if arg.endswith('.json') and os.path.isfile(arg):
        with open(arg, 'r', encoding='utf-8-sig') as f:
            dados_json = f.read()
    else:
        dados_json = arg
    gerar(dados_json)
