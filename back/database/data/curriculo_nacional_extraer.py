"""
Extrae los OA publicados en curriculumnacional.cl a curriculo_nacional_oa.json (lo carga la
migración cargar_oa_curriculum_nacional o `php artisan curriculo:nacional`).

curriculumnacional.cl no responde fuera de Chile, así que se leen las copias del Internet
Archive (web.archive.org, la más reciente de cada página). Una página por curso × asignatura
(/curriculum/<bases>/<asignatura>/<curso>) trae todos sus OA, agrupados por eje, habilidad y actitud.

Uso (requiere requests y beautifulsoup4):
    python curriculo_nacional_extraer.py descargar <carpeta_html>
    python curriculo_nacional_extraer.py generar <carpeta_html> curriculo_nacional_oa.json
"""
import sys, os, re, json, glob, time, unicodedata
from concurrent.futures import ThreadPoolExecutor

SITIO = 'https://www.curriculumnacional.cl'
ARCHIVO = 'https://web.archive.org'
VERSION = 'CURRICULUM_NACIONAL_WEB_2026'
FUENTE = 'Currículum Nacional, Unidad de Currículum y Evaluación (Mineduc), curriculumnacional.cl'

# EPJA (adultos) no tiene niveles en el libro; /curso/ son índices sin OA
EXCLUIR = re.compile(r'/curso/|epja')

NIVELES = {
    'sc-sala-cuna': ['Sala Cuna'], 'nm-nivel-medio': ['Nivel Medio'], 'nt-nivel-transicion': ['Pre-kínder', 'Kínder'],
    **{f'{n}-basico': [f'{n}° Básico'] for n in range(1, 9)},
    '1-medio': ['1° Medio'], '2-medio': ['2° Medio'],
    '3-medio-fg': ['3° Medio'], '3-medio-hc': ['3° Medio'], '4-medio-fg': ['4° Medio'], '4-medio-hc': ['4° Medio'],
    '3-medio-tp': ['3° Medio TP'], '4-medio-tp': ['4° Medio TP'],
}

# Asignaturas que ya tiene el libro (DatosAcademicosSeeder / ImportadorCurriculo): código => nombre
BASE = {
    'LEN': 'Lengua y Literatura', 'MAT': 'Matemática', 'HIS': 'Historia, Geografía y Ciencias Sociales',
    'CIE': 'Ciencias Naturales', 'ING': 'Inglés', 'EFI': 'Educación Física y Salud', 'ART': 'Artes Visuales',
    'MUS': 'Música', 'TEC': 'Tecnología', 'ORI': 'Orientación', 'FIL': 'Filosofía', 'ECI': 'Educación Ciudadana',
    'CCI': 'Ciencias para la Ciudadanía', 'PCI': 'Comunicación integral', 'PDPS': 'Desarrollo personal y social',
    'PICE': 'Interacción y comprensión del entorno', 'REL': 'Religión',
    'LCPO': 'Lengua y Cultura de los Pueblos Originarios Ancestrales', 'LIND': 'Lengua Indígena',
}
# slug de la URL (sin sufijos de curso) => código
SLUGS = {
    'lenguaje-comunicacion': 'LEN', 'lengua-literatura': 'LEN', 'matematica': 'MAT',
    'historia-geografia-ciencias-sociales': 'HIS', 'ciencias-naturales': 'CIE', 'ingles': 'ING', 'ingles-propuesta': 'ING',
    'educacion-fisica-salud': 'EFI', 'artes-visuales': 'ART', 'musica': 'MUS', 'tecnologia': 'TEC', 'orientacion': 'ORI',
    'filosofia': 'FIL', 'educacion-ciudadana': 'ECI', 'religion': 'REL',
    # módulos de Ciencias para la Ciudadanía (3° y 4° Medio)
    'ambiente-sostenibilidad': 'CCI', 'bienestar-salud': 'CCI', 'seguridad-prevencion-autocuidado': 'CCI', 'tecnologia-sociedad': 'CCI',
    'comunicacion-integral': 'PCI', 'desarrollo-personal-social': 'PDPS', 'interaccion-comprension-entorno': 'PICE',
    'lengua-cultura-pueblos-originarios-ancestrales': 'LCPO', 'lengua-indigena': 'LIND',
}
MODULOS_CCI = {'ambiente-sostenibilidad', 'bienestar-salud', 'seguridad-prevencion-autocuidado', 'tecnologia-sociedad'}
VACIAS = {'de', 'del', 'la', 'las', 'el', 'los', 'y', 'e', 'en', 'para', 'a', 'mencion', 'especialidad', 'con'}


def limpiar(t):
    return re.sub(r'\s+', ' ', (t or '').replace('\xa0', ' ')).strip()


def sin_tildes(t):
    return ''.join(c for c in unicodedata.normalize('NFD', t) if unicodedata.category(c) != 'Mn')


# ── descarga ──────────────────────────────────────────────────────────────

def paginas():
    import requests
    r = requests.get(f'{ARCHIVO}/cdx/search/cdx', timeout=300, params={
        'url': 'curriculumnacional.cl/curriculum/', 'matchType': 'prefix', 'filter': 'statuscode:200',
        'collapse': 'urlkey', 'fl': 'original', 'limit': 200000})
    rutas = set()
    for linea in r.text.splitlines():
        ruta = re.sub(r'^https?://(www\.)?curriculumnacional\.cl', '', linea.strip(), flags=re.I)
        if '?' not in ruta and ruta.count('/') == 4 and not EXCLUIR.search(ruta):
            rutas.add(ruta)
    return sorted(rutas)


def descargar(carpeta):
    import requests
    os.makedirs(carpeta, exist_ok=True)
    ses = requests.Session()
    ses.headers['User-Agent'] = 'Mozilla/5.0 (libro-de-clases)'

    def una(ruta):
        nombre = os.path.join(carpeta, ruta.strip('/').replace('/', '__') + '.html')
        if os.path.exists(nombre) and os.path.getsize(nombre) > 5000:
            return
        for intento in range(4):
            try:
                r = ses.get(f'{ARCHIVO}/web/2026id_/{SITIO}{ruta}', timeout=90)
                if r.status_code == 200 and len(r.content) > 5000:
                    open(nombre, 'wb').write(r.content)
                    print('ok', ruta, flush=True)
                    return
                if r.status_code == 404:
                    break
            except Exception as e:
                print('error', ruta, e, flush=True)
            time.sleep(5 * (intento + 1))
        print('FALLÓ', ruta, flush=True)

    with ThreadPoolExecutor(4) as ex:
        list(ex.map(una, paginas()))


# ── lectura de una página ─────────────────────────────────────────────────

def texto_oa(div):
    """Párrafos y viñetas como líneas: 'Demostrar…:\\n- usando…\\n- representando…'."""
    partes = []
    for el in div.find_all(['p', 'li']):
        if el.name == 'p' and el.find_parent('li'):
            continue
        t = limpiar(el.get_text(' '))
        if t:
            partes.append(('- ' if el.name == 'li' else '') + t)
    return '\n'.join(partes) if partes else limpiar(div.get_text(' '))


def leer(ruta):
    from bs4 import BeautifulSoup
    s = BeautifulSoup(open(ruta, encoding='utf-8').read(), 'html.parser')
    migas = [limpiar(li.get_text()) for li in s.select('nav[aria-labelledby*=breadcrumb] li, .breadcrumb li')]
    grupos = []
    for bloque in s.select('div.item-wrappers'):
        padre = bloque.find_parent('div', class_='items-wrapper')
        h3 = padre.find('h3') if padre else None
        items = []
        for w in bloque.select('div.item-wrapper'):
            cod, desc = w.select_one('.number-title'), w.select_one('.field--name-description')
            if not cod or not desc:
                continue
            link = w.select_one('a.link-more')
            items.append({'codigo': limpiar(cod.get_text()).rstrip('.'), 'texto': texto_oa(desc),
                          'priorizado': 'prioritized' in (w.get('class') or []),
                          'url': SITIO + link['href'] if link and link.get('href', '').startswith('/') else None})
        grupos.append({'tipo': (h3.get('id') or '').split('-')[0] if h3 else 'oa',
                       'nombre': limpiar(h3.get_text()) if h3 else None, 'items': items})
    return migas, grupos


# ── consolidación ─────────────────────────────────────────────────────────

SUFIJO = {'Sala Cuna': 'SC', 'Nivel Medio': 'NM', 'Pre-kínder': 'PK', 'Kínder': 'K'}
MINUSCULAS = {'De', 'Del', 'La', 'Las', 'El', 'Los', 'Y', 'E', 'En', 'Para', 'A', 'Con'}


def sufijo(nivel):
    """'3° Medio TP' => '3TP', '1° Básico' => '1B', 'Kínder' => 'K'."""
    if nivel in SUFIJO:
        return SUFIJO[nivel]
    n, resto = nivel.split('° ')
    return n + ('TP' if resto.endswith('TP') else resto[0].upper())


def nombre_propio(nombre):
    """El sitio capitaliza artículos ('Chile y La Región…'): se dejan en minúscula salvo al inicio."""
    palabras = nombre.split(' ')
    return ' '.join([palabras[0]] + [p.lower() if p in MINUSCULAS else p for p in palabras[1:]])


def codigo_nuevo(nombre, usados, tp):
    """'Atención de Párvulos' (TP) => 'TPATEP'; 'Biología Celular y Molecular' => 'BIOCM'."""
    palabras = [p for p in re.split(r'[^A-Za-z0-9]+', sin_tildes(nombre).lower()) if p and p not in VACIAS]
    base = (('TP' if tp else '') + palabras[0][:3] + ''.join(p[0] for p in palabras[1:6])).upper()[:9]
    codigo, n = base, 2
    while codigo in usados:
        codigo, n = f'{base}{n}', n + 1
    usados.add(codigo)
    return codigo


def generar(carpeta, salida):
    ofertas, asignaturas, usados = {}, {}, set(BASE)
    for archivo in sorted(glob.glob(os.path.join(carpeta, '*.html'))):
        _, bases, slug, curso = os.path.basename(archivo)[:-5].split('__')
        niveles = NIVELES.get(curso)
        if not niveles:
            print('sin nivel', archivo)
            continue
        migas, grupos = leer(archivo)
        if not any(g['items'] for g in grupos):
            print('sin OA', archivo)
            continue

        # slug sin sufijo de curso: filosofia-3m, lengua-literatura-4o-medio, educacion-fisica-salud-1…
        raiz = re.sub(r'-(\d(o|m)?(-medio)?|\d)$', '', slug)
        tp = 'tecnico-profesional' in bases
        if raiz in SLUGS:
            codigo = SLUGS[raiz]
            nombre = BASE[codigo]
        else:
            corto = nombre_propio(re.sub(r'^Especialidad ', '', migas[3] if len(migas) > 3 else slug))
            nombre = (f'TP {corto}' if tp else corto)[:100]
            codigo = asignaturas.get(nombre) or codigo_nuevo(corto, usados, tp)
        asignaturas[nombre] = codigo

        modulo = migas[3] if raiz in MODULOS_CCI and len(migas) > 3 else None
        habilidades, actitudes, oa = [], [], []
        for g in grupos:
            if g['tipo'] == 'habilidad':
                if g['nombre'] and g['nombre'] not in habilidades:
                    habilidades.append(g['nombre'])
            elif g['tipo'] == 'actitud':
                actitudes += [f"{i['codigo']}: {i['texto']}" for i in g['items']]
            else:
                for i in g['items']:
                    # Los códigos de TP ("OA 1") se repiten entre especialidades: se antepone la asignatura
                    cod = re.sub(r'\s+', '-', i['codigo'])
                    cod = re.sub(r'(?<=OA-)(\d)(?=$|[a-z])', lambda m: '0' + m.group(1), cod)  # OA-1 => OA-01 (ordena bien)
                    if re.fullmatch(r'OA-?\d+[a-z]?', cod):
                        cod = f'{codigo}-{cod}'  # TP: "OA 1" se repite en todas las especialidades
                    eje = g['nombre'] if g['nombre'] and g['tipo'] != 'oa' else None
                    if modulo:
                        eje = f'{modulo}: {eje}' if eje else modulo
                    oa.append({'codigo': cod, 'eje': eje, 'texto': i['texto'], 'priorizado': i['priorizado'], 'url': i['url']})

        clave = (codigo, curso, slug if tp or raiz not in SLUGS else raiz)
        # La misma página existe con dos URL (7o-basico-2-medio y 7o-basico-2o-medio): queda la de más OA
        if clave in ofertas and len(ofertas[clave]['oa']) >= len(oa):
            continue
        ofertas[clave] = {'bases': migas[2] if len(migas) > 2 else bases, 'asignatura': nombre, 'asignatura_codigo': codigo,
                          'niveles': niveles, 'url': f'{SITIO}/curriculum/{bases}/{slug}/{curso}',
                          'habilidades': habilidades, 'actitudes': [a[:300] for a in actitudes], 'oa': oa}

    # Inglés (Propuesta) solo donde no hay programa oficial de Inglés en ese curso
    oficiales = {c for (cod, c, r) in ofertas if cod == 'ING' and r == 'ingles'}
    ofertas = {k: v for k, v in ofertas.items() if not (k[2] == 'ingles-propuesta' and k[1] in oficiales)}

    # Una oferta por nivel (NT = Pre-kínder y Kínder) y sin OA repetidos en un mismo nivel
    lista, vistos = [], set()
    for o in ofertas.values():
        for nivel in o.pop('niveles'):
            oas = [dict(a) for a in o['oa'] if (a['codigo'], nivel) not in vistos and not vistos.add((a['codigo'], nivel))]
            if oas:
                lista.append({**o, 'nivel': nivel, 'oa': oas})

    # El código es único en el catálogo: el que se repite entre niveles ("…-3y4-OAC-01") lleva el nivel
    from collections import Counter
    veces = Counter(a['codigo'] for o in lista for a in o['oa'])
    for o in lista:
        for a in o['oa']:
            if veces[a['codigo']] > 1:
                a['codigo'] = f"{a['codigo']}-{sufijo(o['nivel'])}"
    assert len({a['codigo'] for o in lista for a in o['oa']}) == sum(len(o['oa']) for o in lista), 'códigos repetidos'
    assert all(len(a['codigo']) <= 40 for o in lista for a in o['oa'])

    lista.sort(key=lambda o: (o['asignatura_codigo'] not in BASE, o['asignatura'], [n for v in NIVELES.values() for n in v].index(o['nivel'])))

    datos = {'version': VERSION, 'fuente': FUENTE, 'fuente_url': f'{SITIO}/curriculum',
             'extraido': time.strftime('%Y-%m-%d'), 'ofertas': lista}
    json.dump(datos, open(salida, 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
    print(len(lista), 'cursos × asignatura;', sum(len(o['oa']) for o in lista), 'OA;',
          len({o['asignatura_codigo'] for o in lista}), 'asignaturas')


if __name__ == '__main__':
    if sys.argv[1] == 'descargar':
        descargar(sys.argv[2])
    else:
        generar(sys.argv[2], sys.argv[3])
