package agrochamba.com.data

import com.squareup.moshi.*
import com.squareup.moshi.Types
import java.lang.reflect.Type

/**
 * Adaptador personalizado para manejar Title que puede venir como string o como objeto
 */
class TitleAdapter {
    @FromJson
    fun fromJson(reader: JsonReader): Title? {
        return when (reader.peek()) {
            JsonReader.Token.STRING -> {
                // Si viene como string, crear un objeto Title con ese string
                val stringValue = reader.nextString()
                Title(rendered = stringValue)
            }
            JsonReader.Token.BEGIN_OBJECT -> {
                // Si viene como objeto, parsearlo normalmente
                reader.beginObject()
                var rendered: String? = null
                while (reader.hasNext()) {
                    when (reader.nextName()) {
                        "rendered" -> rendered = reader.nextString()
                        else -> reader.skipValue()
                    }
                }
                reader.endObject()
                Title(rendered = rendered)
            }
            JsonReader.Token.NULL -> {
                reader.skipValue()
                null
            }
            else -> {
                reader.skipValue()
                null
            }
        }
    }

    @ToJson
    fun toJson(writer: JsonWriter, value: Title?) {
        if (value == null) {
            writer.nullValue()
        } else {
            writer.beginObject()
            writer.name("rendered")
            writer.value(value.rendered)
            writer.endObject()
        }
    }
}

/**
 * Factory para registrar el adaptador de Title
 */
class TitleAdapterFactory : JsonAdapter.Factory {
    override fun create(type: Type, annotations: MutableSet<out Annotation>, moshi: Moshi): JsonAdapter<*>? {
        if (Types.getRawType(type) == Title::class.java) {
            val adapter = TitleAdapter()
            return object : JsonAdapter<Title>() {
                override fun fromJson(reader: JsonReader): Title? = adapter.fromJson(reader)
                override fun toJson(writer: JsonWriter, value: Title?) = adapter.toJson(writer, value)
            }
        }
        return null
    }
}

