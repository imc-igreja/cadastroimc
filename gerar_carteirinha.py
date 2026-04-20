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
# FRENTE (card x: 146–382)
#   Campo amarelo Registro:  x0=160.6 top=295.3 x1=215.6 bot=313.4 → centro x=188, y_pdf=543-313=230
#   Campo Nome:              x0=220.4 top=304.8 x1=366.4 bot=323.1 → x=228, y_pdf=543-323=220
#   Campo Cargo:             x0=219.4 top=335.5 x1=288.1 bot=353.8 → x=227, y_pdf=543-354=189
#   Campo CPF:               x0=294.3 top=335.5 x1=360.6 bot=353.8 → x=302, y_pdf=543-354=189
#   Campo RG:                x0=219.9 top=369.8 x1=282.4 bot=388.1 → x=227, y_pdf=543-388=155
#   Campo Ordenação:         x0=294.3 top=369.8 x1=360.6 bot=388.1 → x=302, y_pdf=543-388=155
#   Foto (avatar):           x0=160.6 top=317.5 x1=215.7 bot=370.3 → x=163, y_pdf=543-370=173
#
# VERSO (card x: 386–621)
#   Campo Nacionalidade:     x0=405.7 top=272.2 x1=492.7 bot=290.5 → x=413, y_pdf=543-290=253
#   Campo Naturalidade:      x0=511.4 top=272.2 x1=598.4 bot=290.5 → x=519, y_pdf=543-290=253
#   Campo Validade:          x0=405.7 top=309.0 x1=492.7 bot=327.3 → x=413, y_pdf=543-327=216
#   Campo Estado Civil:      x0=511.4 top=307.5 x1=598.4 bot=325.8 → x=519, y_pdf=543-326=217
#   QR code:                 x0=441.2 top=358.2 x1=491.7 bot=382.4 → x=441, y_pdf=543-382=161

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
            
            # Dimensões exatas do avatar azul no template
            # x0=160.6 top=317.5 x1=215.7 bot=370.3 → w=55.1, h=52.8
            target_width = 55
            target_height = 53
            
            # Calcula o aspect ratio
            img_ratio = img_width / img_height
            target_ratio = target_width / target_height
            
            # Ajusta dimensões mantendo proporção e preenchendo TODA a área (cover)
            if img_ratio > target_ratio:
                new_height = target_height
                new_width = new_height * img_ratio
                x_offset = (new_width - target_width) / 2
                y_offset = 0
            else:
                new_width = target_width
                new_height = new_width / img_ratio
                x_offset = 0
                y_offset = (new_height - target_height) / 2
            
            # Posição exata do avatar
            foto_x = 161
            foto_y = 173
            
            # Raio dos cantos arredondados (igual ao template)
            corner_radius = 8

            img = ImageReader(foto_path)
            c.saveState()

            # Fundo branco para cobrir o ícone de avatar do template
            from reportlab.lib import colors as rl_colors
            c.setFillColor(rl_colors.white)
            p_bg = c.beginPath()
            p_bg.roundRect(foto_x, foto_y, target_width, target_height, corner_radius)
            c.drawPath(p_bg, fill=1, stroke=0)

            # Clip e desenha a foto cobrindo toda a área
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

            # Posição do QR no verso — sobrepõe o QR estático grande
            # x0=386.4 top=326.2 x1=621.3 bot=399.4 (rect grande do verso)
            # QR estático fica em x≈535, top≈330, bot≈395 → y_pdf=543-395=148
            qr_x = 535
            qr_y = 148
            qr_size = 62

            c.drawImage(ImageReader(qr_buf), qr_x, qr_y, width=qr_size, height=qr_size)
        except Exception as e:
            print(f"Erro ao gerar QR code: {e}")

    # Registro (campo amarelo) — x0=160.6 top=295.3 x1=215.6 bot=313.4
    escrever(c, trunc(d.get('registro', ''), 8),
             x=188, y=236, size=7, bold=True, cor=TEXTO_ESC, align='center')

    # Nome — x0=220.4 top=304.8 x1=366.4 bot=323.1
    escrever(c, trunc(d.get('nome', ''), 30),
             x=228, y=226, size=7, bold=False, cor=TEXTO_ESC)

    # Cargo — x0=219.4 top=335.5 x1=288.1 bot=353.8
    escrever(c, trunc(d.get('cargo', ''), 13),
             x=227, y=195, size=6.5, bold=False, cor=TEXTO_ESC)

    # CPF — x0=294.3 top=335.5 x1=360.6 bot=353.8
    escrever(c, trunc(d.get('cpf', ''), 14),
             x=302, y=195, size=6.5, bold=False, cor=TEXTO_ESC)

    # RG — x0=219.9 top=369.8 x1=282.4 bot=388.1
    escrever(c, trunc(d.get('rg', ''), 13),
             x=227, y=161, size=6.5, bold=False, cor=TEXTO_ESC)

    # Ordenação — x0=294.3 top=369.8 x1=360.6 bot=388.1
    escrever(c, fmt_data(d.get('data_ordenacao', '')),
             x=302, y=161, size=6.5, bold=False, cor=TEXTO_ESC)

    # ── VERSO ──────────────────────────────────────────────────────

    # Nacionalidade — x0=405.7 top=272.2 x1=492.7 bot=290.5
    escrever(c, trunc(d.get('nacionalidade', 'Brasileira'), 14),
             x=413, y=259, size=6.5, bold=False, cor=TEXTO_ESC)

    # Naturalidade — x0=511.4 top=272.2 x1=598.4 bot=290.5
    escrever(c, trunc(d.get('naturalidade', ''), 18),
             x=519, y=259, size=6, bold=False, cor=TEXTO_ESC)

    # Validade — x0=405.7 top=309.0 x1=492.7 bot=327.3
    escrever(c, fmt_data(d.get('data_validade', '')),
             x=413, y=222, size=6.5, bold=False, cor=TEXTO_ESC)

    # Estado Civil — x0=511.4 top=307.5 x1=598.4 bot=325.8
    escrever(c, trunc(d.get('estado_civil', ''), 13),
             x=519, y=223, size=6.5, bold=False, cor=TEXTO_ESC)

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
